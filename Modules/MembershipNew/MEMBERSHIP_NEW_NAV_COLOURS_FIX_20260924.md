# Membership New - Navigation Button Colours Fix

Date: 24 Sep 2026

## Issue
The main Membership New navigation bar continued to use the neutral `.mn-nav a` background, so the different system-standard button colours were not visible even though action buttons were already colour-coded.

## Fix
- Applied distinct system-standard colours directly to `.mn-nav > a` buttons.
- Kept white text on inactive coloured buttons.
- Kept the selected/active page white with black text and a coloured border/accent.
- Applied through the shared Membership New stylesheet so the fix is visible on every module page.
- Kept mobile horizontal scrolling and existing responsive behaviour unchanged.
- No database changes.
