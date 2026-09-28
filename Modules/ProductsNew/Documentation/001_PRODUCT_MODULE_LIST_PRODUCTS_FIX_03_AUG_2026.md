# Products New - List Products Fix (03 Aug 2026)

Implemented from `001. Product Module.odt`:

- Product column reduced by 30%.
- SKU / Barcode column reduced by 35%.
- Long SKU/barcode values use a `Click to View` modal.
- Modal shows product name, SKU, current stock, purchase price (permission controlled), and selling price.
- Purchase Price and Selling Price columns added immediately after SKU / Barcode.
- Brand reduced by 15%, Unit by 30%, Tax by 40%, and Status by 25%.
- Horizontal table scrollbar remains available at the bottom.
- Table font size reduced by one step and headings may wrap to two or three lines.
- New permission: `products_new.purchase_price.view`.

The purchase-price permission controls both the list column and the value inside the details modal. The JSON list endpoint also omits purchase-price values when permission is not granted.
