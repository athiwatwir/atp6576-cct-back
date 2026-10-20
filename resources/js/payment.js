import Alpine from 'alpinejs';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers ?? {}),
        },
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(firstError || data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    }

    return data;
}

function createOmiseToken(card) {
    return new Promise((resolve, reject) => {
        if (!window.Omise) {
            reject(new Error('โหลดระบบชำระบัตรไม่สำเร็จ กรุณารีเฟรชหน้า'));
            return;
        }

        window.Omise.createToken('card', card, (statusCode, response) => {
            if (statusCode === 200 && response.id) {
                resolve(response.id);
            } else {
                reject(new Error(response.message || 'ข้อมูลบัตรไม่ถูกต้อง'));
            }
        });
    });
}

function paymentPage(config) {
    return {
        config,
        state: config.state,
        method: null,
        busy: false,
        error: '',
        remaining: 0,
        countdownTimer: null,
        pollTimer: null,
        card: { name: '', number: '', expiry: '', cvc: '' },

        init() {
            if (this.config.omisePublicKey && window.Omise) {
                window.Omise.setPublicKey(this.config.omisePublicKey);
            }

            const payment = this.state.payment;

            if (payment?.status === 'pending' && payment.method === 'thai_qr' && payment.qr_image) {
                this.method = 'thai_qr';
                this.watchQr();
            } else if (payment?.status === 'failed' && payment.failure_code !== 'superseded' && payment.failure_message) {
                this.method = payment.method === 'thai_qr' ? 'thai_qr' : 'credit_card';
                this.error = payment.failure_message;
            } else if (this.config.methods.length === 1) {
                this.method = this.config.methods[0];
            }
        },

        get paid() {
            return this.state.paid;
        },

        get quotes() {
            return this.config.quotes || {};
        },

        get orderTotal() {
            return Object.values(this.quotes)[0]?.order_amount ?? this.state.payment?.order_amount ?? 0;
        },

        get currentQuote() {
            const payment = this.state.payment;

            if (this.qr) {
                return {
                    fee_amount: payment.fee_amount,
                    fee_label: payment.fee_label,
                    total: payment.amount,
                };
            }

            return this.method ? this.quotes[this.method] ?? null : null;
        },

        get payableTotal() {
            if (this.paid && this.state.payment) {
                return this.state.payment.amount;
            }

            return this.currentQuote?.total ?? this.orderTotal;
        },

        number(value) {
            return Number(value ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        get qr() {
            const payment = this.state.payment;

            return payment?.method === 'thai_qr' && payment.status === 'pending' && payment.qr_image ? payment : null;
        },

        get qrExpired() {
            return this.qr !== null && this.remaining <= 0;
        },

        get countdown() {
            const minutes = Math.floor(this.remaining / 60);
            const seconds = this.remaining % 60;

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        get expiresAtText() {
            if (!this.qr?.expires_at) return '';

            return new Date(this.qr.expires_at).toLocaleString('th-TH', {
                dateStyle: 'medium',
                timeStyle: 'short',
            });
        },

        amountText(value) {
            return `${Number(value ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} THB`;
        },

        select(method) {
            if (this.busy) return;
            this.method = method;
            this.error = '';
        },

        async createQr() {
            this.busy = true;
            this.error = '';

            try {
                this.state = await request(this.config.urls.thaiQr, { method: 'POST', body: '{}' });
                this.watchQr();
            } catch (exception) {
                this.error = exception.message;
            } finally {
                this.busy = false;
            }
        },

        watchQr() {
            this.stopTimers();
            this.tick();
            this.countdownTimer = setInterval(() => this.tick(), 1000);
            this.pollTimer = setInterval(() => this.poll(), 4000);
        },

        tick() {
            const expiresAt = this.qr?.expires_at ? new Date(this.qr.expires_at).getTime() : 0;
            this.remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));

            if (this.remaining <= 0) {
                clearInterval(this.countdownTimer);
            }
        },

        async poll() {
            try {
                const next = await request(this.config.urls.status);
                this.state = next;

                if (next.paid || next.payment?.status !== 'pending') {
                    this.stopTimers();

                    if (!next.paid && next.payment?.failure_message) {
                        this.error = next.payment.failure_message;
                    }
                }
            } catch {
                // Polling retries on the next interval.
            }
        },

        stopTimers() {
            clearInterval(this.countdownTimer);
            clearInterval(this.pollTimer);
        },

        saveQr() {
            const image = this.qr?.qr_image;
            if (!image) return;

            const link = document.createElement('a');
            link.href = image;
            link.download = `QR-${this.state.order_no}.${image.startsWith('data:image/svg') ? 'svg' : 'png'}`;
            link.target = '_blank';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        },

        formatNumber() {
            const digits = this.card.number.replace(/\D/g, '').slice(0, 19);
            this.card.number = digits.replace(/(.{4})/g, '$1 ').trim();
        },

        formatExpiry() {
            const digits = this.card.expiry.replace(/\D/g, '').slice(0, 4);
            this.card.expiry = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
        },

        cardErrors() {
            const number = this.card.number.replace(/\D/g, '');
            const [month, year] = this.card.expiry.split('/');

            if (this.card.name.trim() === '') return 'กรุณากรอกชื่อบนบัตร';
            if (number.length < 13) return 'หมายเลขบัตรไม่ครบ';
            if (!month || !year || Number(month) < 1 || Number(month) > 12 || year.length !== 2) return 'วันหมดอายุไม่ถูกต้อง (ดด/ปป)';
            if (!/^\d{3,4}$/.test(this.card.cvc)) return 'CVV ไม่ถูกต้อง';

            return null;
        },

        async tokenize() {
            const number = this.card.number.replace(/\D/g, '');

            if (this.config.fakeCard) {
                if (number.endsWith('0002')) return this.config.fakeTokens.fail;
                if (number.endsWith('3220')) return this.config.fakeTokens['3ds'];

                return this.config.fakeTokens.success;
            }

            const [month, year] = this.card.expiry.split('/');

            return createOmiseToken({
                name: this.card.name.trim(),
                number,
                expiration_month: Number(month),
                expiration_year: 2000 + Number(year),
                security_code: this.card.cvc,
            });
        },

        async payCard() {
            const invalid = this.cardErrors();
            if (invalid) {
                this.error = invalid;
                return;
            }

            this.busy = true;
            this.error = '';

            try {
                const token = await this.tokenize();
                const next = await request(this.config.urls.card, { method: 'POST', body: JSON.stringify({ token }) });
                this.state = next;
                this.card.cvc = '';

                if (next.payment?.authorize_uri) {
                    window.location.href = next.payment.authorize_uri;
                    return;
                }

                if (!next.paid) {
                    this.error = next.payment?.failure_message || 'ชำระเงินไม่สำเร็จ กรุณาตรวจสอบบัตรหรือใช้บัตรอื่น';
                }
            } catch (exception) {
                this.error = exception.message;
            } finally {
                this.busy = false;
            }
        },

        async simulate(result) {
            this.busy = true;
            this.error = '';

            try {
                this.state = await request(this.config.urls.simulate, { method: 'POST', body: JSON.stringify({ state: result }) });
                this.stopTimers();

                if (!this.state.paid) {
                    this.error = this.state.payment?.failure_message || 'ชำระเงินไม่สำเร็จ';
                }
            } catch (exception) {
                this.error = exception.message;
            } finally {
                this.busy = false;
            }
        },
    };
}

window.Alpine = Alpine;
window.paymentPage = paymentPage;

Alpine.start();
