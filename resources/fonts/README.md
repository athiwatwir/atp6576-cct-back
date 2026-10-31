# ฟอนต์ของ PDF (ใบเสร็จรับเงิน)

`NotoSansThai-Regular.ttf` และ `NotoSansThai-Bold.ttf` คือ Noto Sans Thai จาก Google Fonts (สัญญาอนุญาต SIL Open Font License 1.1 ดู `OFL.txt`) ใช้ใน `App\Support\Receipts::pdf()`

## ที่มา

ใช้ไฟล์ static จาก Google Fonts API ซึ่งมีทั้งตัวอักษรไทย ละติน ตัวเลข และ ฿ (ไฟล์จาก repo notofonts มีแต่ไทย)

```bash
curl -A "Wget/1.0" "https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;700"
# ดาวน์โหลด URL .ttf ของน้ำหนัก 400 และ 700 ที่ได้ แล้วตั้งชื่อเป็น NotoSansThai-Regular.ttf / NotoSansThai-Bold.ttf
```

## แก้ให้ใช้กับ mPDF ได้

mPDF ไม่รองรับ `MarkGlyphSets` ใน OpenType (ถ้าเปิด `useOTL` จะ error) และถ้าปิด `useOTL` วรรณยุกต์ที่อยู่บนสระบน (เช่น ยี่ ที่) จะซ้อนทับจนมองไม่เห็น จึงตัด `MarkGlyphSets` ออกจากไฟล์ด้วย [fontTools](https://github.com/fonttools/fonttools) แล้วเปิด `useOTL`

```python
from fontTools.ttLib import TTFont

for path in ["NotoSansThai-Regular.ttf", "NotoSansThai-Bold.ttf"]:
    font = TTFont(path)
    for tag in ("GSUB", "GPOS"):
        for lookup in font[tag].table.LookupList.Lookup:
            if lookup.LookupFlag & 0x0010:  # UseMarkFilteringSet
                lookup.LookupFlag &= ~0x0010
                lookup.MarkFilteringSet = None
    gdef = font["GDEF"].table
    gdef.MarkGlyphSetsDef = None
    gdef.Version = 0x00010000
    font.save(path)
```

ถ้าเปลี่ยนไฟล์ฟอนต์ ให้ลบแคชฟอนต์ของ mPDF ใน `storage/app/mpdf/mpdf/ttfontdata/notosansthai*` ด้วย
