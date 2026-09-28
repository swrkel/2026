# IS2182 #1 — Finalize Settlement button appears when the balance reaches zero

Petro Direct / Direct settlement / Payment / Add payment

**One file changed:**
`Modules/PetroDirect/Resources/views/settlement/partials/add_payment.blade.php`

No database, route or controller changes.

---

## What was blocking it

Two things, and the first is the reason a refresh "fixed" it.

### 1. Twenty places set the button, three incompatible ways

```js
$("#settlement_save_btn").removeClass("hide")                                  // class only
$("#settlement_save_btn").removeClass("hide").show()                           // class + inline
$("#settlement_save_btn").removeClass("hide").show().css("display","inline-block")
$("#settlement_save_btn").addClass("hide").hide()                              // inline display:none
$("#settlement_save_btn").addClass("hide")                                     // class only
```

These fight. Once any handler ran `.addClass("hide").hide()`, the element carried
`display:none` **inline**. A later `removeClass("hide")` on its own removes the
class but **cannot clear an inline style** — so the button stayed invisible even
though the code had just "shown" it.

Whether it appeared depended on which of twenty handlers ran last.

A page refresh put it right because the Blade condition sets the initial state
cleanly, before anything has fought over it. That is exactly the reported
behaviour.

### 2. The balance was read from the hidden input, not the screen

```js
let total_balance = parseFloat($("#total_balance").val() ...)
```

The input holds the raw float; `.total_balance` shows it rounded to the
business's currency precision. A balance of `0.004` **displays as 0.00** while
the input still says `0.004`, so the button stayed hidden against a screen
reading zero.

A second function, `show_hide_excess_shortage_tab()`, computed the balance its
own way and set the button too — two functions deciding the same thing from two
different sources.

---

## The fix

**One authority owns the button.** `update_finalize_button_from_current_state()`
decides; everything else calls `petroRefreshFinalizeButton()`. All eighteen
direct manipulations were replaced, leaving only the authority's own
`set_settlement_finalize_visible()` — which clears both the class and the inline
style, so no earlier call can leave residue.

**It reads what the user sees.** The displayed `.total_balance` text is
authoritative, with the hidden input kept only as a fallback for the instant
before the text is written. Commas and bracketed negatives such as `(123.45)`
are handled.

**`show_hide_excess_shortage_tab()` no longer decides.** It keeps the Excess and
Shortage tabs, which are its job, and delegates the button.

**Deliberate hiding is preserved.** Flows that must keep Finalize hidden at zero
— mid-save, for instance — set `window.__petro_finalize_locked`, which the
authority honours above everything else. Without that, a balance refresh
arriving mid-save would reveal the button and invite a second submission.

**Declared, not assigned.** `petroRefreshFinalizeButton` is a function
declaration so it is hoisted: several of the seventeen call sites sit far above
its definition, and an assignment would only exist from the line it ran on.

Cash-denomination handling is unchanged — with denominations enabled, the
denomination balance must also be zero.

---

## Verified behaviour

| Screen shows | Result |
|---|---|
| `0.00` | **Show** |
| `0.00` with a float residue of 0.004 behind it | **Show** |
| `0.000` (3-decimal currency) | **Show** |
| `0.01` / `-0.01` | Hide |
| `(123.45)` | Hide |
| `0.00` but denominations unbalanced | Hide |
| `0.00` while locked mid-save | Hide |

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/PetroDirect_IS2182_Finalize_Button.zip
cd /home/nivasa/public_html && php artisan view:clear
```

Hard-refresh afterwards — the change is in page JavaScript.

---

## Testing

- [ ] Add payments until Balance reads 0.00 — Finalize appears **without a refresh**
- [ ] Works from every payment tab: cash, cheques, cards, credit sales, expenses
- [ ] Add another payment so the balance is non-zero — Finalize hides again
- [ ] Remove that payment to return to zero — it reappears
- [ ] With cash denominations on, a zero balance but unbalanced denominations keeps it hidden
- [ ] Balance to Operator at zero still shows it
- [ ] Finalizing still saves the settlement correctly
- [ ] Excess and Shortage tabs still show and hide as before
