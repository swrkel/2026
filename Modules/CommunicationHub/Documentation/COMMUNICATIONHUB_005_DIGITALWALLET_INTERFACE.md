# COMMUNICATIONHUB_005 - DigitalWallet Integration Interface

CommunicationHub remains 100% standalone and does not depend on the legacy Wallet module.

## Included

- Wallet connector interface
- Null wallet connector for testing
- Wallet charge request / response DTOs
- Charge-before-send workflow
- Wallet transaction reference fields on messages
- Estimated / actual cost tracking
- Currency tracking
- Wallet settings

## Integration Rule

CommunicationHub must not read or write wallet tables directly. A future DigitalWallet module should bind its own connector implementation to `CommunicationHubWalletConnectorInterface`.
