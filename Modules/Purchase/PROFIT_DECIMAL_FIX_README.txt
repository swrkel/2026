Purchase Add Purchase Entry - Profit decimal validation fix - 25 Sep 2026

Issue:
Chrome HTML5 validation could reject the dynamically recalculated Profit % value
with a message such as "Please enter a valid value. The two nearest valid values are...".
This blocked Save even when the required purchase fields were correctly completed.

Cause:
Profit % used type=number with step=0.000001 but without min. Per HTML step
validation, the initial value attribute can become the step base. The JavaScript later
recalculates and writes a new profit value (for example 11.74), which can be a valid
number but still fail the browser's step grid because the original product profit had
more floating-point precision.

Fix:
Profit % remains type=number but now uses step=any, allowing any decimal precision.
No purchase calculation, product price, stock, tax, tank, payment, accounting, or save
workflow has been changed.

Database changes: None.
Changed runtime file:
Purchase/Resources/assets/js/purchase-entry-create.js
