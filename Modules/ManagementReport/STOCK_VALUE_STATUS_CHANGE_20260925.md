# Stock Value Status change - 25 Sep 2026

The Stock Value Status section now displays:

1. Opening Stock
2. Purchased Value
3. Sales Return
4. Stock Adjustment
5. Sold
6. Purchase Return
7. Balance Stock

Formula:

`Balance Stock = Opening Stock + Purchased Value + Sales Return + Stock Adjustment - Sold - Purchase Return`

Stock-adjustment values are signed: quantity increases display as positive values and quantity reductions display as negative values.

The existing historically calculated ending stock value remains the authoritative Balance Stock and continues to be published as `stock_value.payload.total`, preserving Financial Status II and Final Review compatibility.
