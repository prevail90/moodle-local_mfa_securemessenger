# Moodle MFA Secure Messenger factor

Secure Messenger is a Moodle MFA factor that sends one-time pass codes through administrator-defined Signal, WhatsApp, Telegram, or custom messenger options.

Install this repository at:

```text
admin/tool/mfa/factor/securemessenger
```

The component name is `factor_securemessenger`.

## Requirements

- Moodle 4.5 or newer.
- Moodle MFA (`tool_mfa`).
- At least one enabled Moodle SMS gateway.

## Installation

Install the plugin, then go to Site administration > Plugins > Multi-factor authentication > Secure Messenger. Configure an enabled Moodle SMS gateway for every messenger option that users may select. Enable the factor only after at least one option has a gateway.

For WhatsApp, Signal, Telegram, or similar delivery, configure or install an SMS gateway provider that can route to that service, then map the messenger option to that gateway in this factor's settings.

## How It Works

The site administrator can enable Signal, WhatsApp, Telegram, and two custom options. Each option is linked to an enabled Moodle SMS gateway instance from Site administration > Plugins > SMS > Manage SMS gateways.

Users set up the factor from their MFA preferences by choosing one of the configured messenger options. Signal and WhatsApp ask for a country code and phone number, then store the number as international digits without a leading plus sign. Telegram and custom options ask for the destination expected by that gateway. Moodle sends a verification code through the mapped gateway, and the factor stores the chosen option for future login codes.

Signal and WhatsApp can optionally check whether the submitted phone number has an account before the first setup OTP is sent. Configure a separate account-check SMS gateway on the factor settings page. The factor triggers that gateway through Moodle's normal SMS gateway manager, and the check passes when the gateway reports `gateway_sent`. Configure the gateway URL, parameters, and success condition to perform the account lookup.

The plugin uses Moodle's `core_sms\manager::send()` API with `issensitive` enabled, so OTP message content is not stored by the SMS subsystem.

For `smsgateway_customapi`, leave the gateway country code setting blank for these messenger gateways so Moodle does not prepend another country code. For Signal gateways that need E.164 numbers with a leading plus sign, add the plus in the gateway parameters, for example `recipients=["+{{recipient}}"]`. For WAHA WhatsApp gateways, use the digits directly, for example `chatId={{recipient}}@c.us`.

## Privacy And External Services

Secure Messenger sends the user's phone number or configured destination and one-time verification code to the SMS gateway selected by the site administrator. If an optional Signal or WhatsApp account-check gateway is configured, it also receives the phone number and account-check request during setup.

The gateway can be an external service. Site administrators are responsible for selecting providers, configuring their credentials, reviewing their privacy terms, and informing users as required by their institution's privacy policy.

The plugin stores the selected option and destination in Moodle MFA's core records. One-time codes are transient and SMS message content is marked sensitive.

## Operations And Recovery

If a verification message does not arrive, users should first retry after checking the selected gateway and destination. Site administrators should maintain another enabled MFA factor or an established account-recovery process, so users can regain access when a messenger account, phone number, or gateway becomes unavailable.

## Support

Report bugs and feature requests through the [GitHub issue tracker](https://github.com/prevail90/moodle-local_mfa_securemessenger/issues).
