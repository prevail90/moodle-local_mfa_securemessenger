<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the secure messenger MFA factor.
 *
 * @package     factor_securemessenger
 * @copyright   2026 prevail90
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action:manage'] = 'Manage secure messenger';
$string['action:revoke'] = 'Remove secure messenger';
$string['destination'] = 'Messenger destination';
$string['destination_help'] = 'For Signal and WhatsApp, enter a country and phone number. For Telegram or custom options, enter the username, phone number, or address expected by the selected gateway.';
$string['editdestination'] = 'Edit destination';
$string['error:accountcheckexception'] = 'The messenger account check failed: {$a}';
$string['error:accountcheckgatewayfailed'] = 'The messenger account check gateway returned {$a}. Try again later or contact the site administrator.';
$string['error:emptydestination'] = 'Enter a messenger destination.';
$string['error:emptyphonecountry'] = 'Choose a country code.';
$string['error:emptyphonenumber'] = 'Enter a phone number.';
$string['error:emptyverification'] = 'Empty code. Try again.';
$string['error:invalidoption'] = 'Choose a configured secure messenger option.';
$string['error:invalidphonenumber'] = 'Enter a valid phone number.';
$string['error:nooptions'] = 'No secure messenger options are configured. Ask the site administrator to enable an option and map it to an SMS gateway.';
$string['error:notsetup'] = 'Secure messenger is not set up.';
$string['error:optionunavailable'] = 'The selected messenger option is no longer available.';
$string['error:otpsendfailed'] = 'The verification code could not be sent. The SMS gateway returned {$a}. Check the selected gateway settings and try again.';
$string['error:wrongverification'] = 'Wrong code. Try again.';
$string['info'] = 'Have a one-time code sent through a secure messenger.';
$string['logindesc'] = '{$a->option} message containing a 6-digit code sent to {$a->destination}';
$string['loginoption'] = 'Use secure messenger';
$string['loginskip'] = 'I did not receive a code';
$string['loginsubmit'] = 'Continue';
$string['logintitle'] = 'Enter the verification code sent to your secure messenger';
$string['managefactor'] = 'Manage secure messenger';
$string['managefactorbutton'] = 'Manage';
$string['manageinfo'] = 'You are using {$a->option} at {$a->destination} to authenticate.';
$string['messagestring'] = '{$a->code} is your {$a->fullname} one-time security code via {$a->option}.

@{$a->url} #{$a->code}';
$string['messengeroption'] = 'Secure messenger';
$string['optionunavailable'] = 'Unavailable option';
$string['phonecountry'] = 'Country code';
$string['phonenumber'] = 'Phone number';
$string['pluginname'] = 'Secure messenger';
$string['privacy:metadata'] = 'The secure messenger factor does not store personal data outside the MFA subsystem.';
$string['revokefactorconfirmation'] = 'Remove secure messenger {$a}?';
$string['settings:shortdescription'] = 'Send one-time codes through Signal, WhatsApp, Telegram, or another configured secure messenger.';
$string['settings:duration'] = 'Validity duration';
$string['settings:duration_help'] = 'The period of time that the code is valid.';
$string['settings:customoption'] = 'Custom option {$a}';
$string['settings:heading'] = 'Users receive a one-time code through the secure messenger option they choose. Signal, WhatsApp, and Telegram are fixed options. Two custom options can be named by the site administrator. Each enabled option is mapped to an enabled SMS gateway.';
$string['settings:accountcheckgateway'] = 'Account check SMS gateway';
$string['settings:accountcheckgateway_help'] = 'Optional. Select a separate SMS gateway instance to trigger before the first setup OTP is sent. The check passes when this gateway reports sent. Configure the selected gateway URL, parameters, and success condition to perform the account lookup.';
$string['settings:optionenabled'] = 'Enable option';
$string['settings:optionenabled_help'] = 'Enabled options are shown to users during secure messenger setup when they also have a name and gateway.';
$string['settings:optiongateway'] = 'SMS gateway';
$string['settings:optiongateway_help'] = 'Select the SMS gateway instance that sends messages for this option, or <a href="{$a}">create a new gateway</a>.';
$string['settings:optionheading'] = '{$a}';
$string['settings:optionenablednamed'] = 'Enable {$a}';
$string['settings:optiongatewaynamed'] = '{$a} SMS gateway';
$string['settings:accountcheckgatewaynamed'] = '{$a} account check SMS gateway';
$string['settings:optionname'] = 'Option name';
$string['settings:optionname_help'] = 'The name shown to users for this custom messenger option.';
$string['settings:setupdesc'] = 'To use secure messenger MFA, you first need to <a href="{$a}">set up an SMS gateway</a>.';
$string['setupcodedesc'] = 'A verification code was sent through {$a->option} to {$a->destination}.';
$string['setupfactor'] = 'Set up secure messenger';
$string['setupfactorbutton'] = 'Set up';
$string['setupsubmitcode'] = 'Save';
$string['setupsubmitdestination'] = 'Send code';
$string['summarycondition'] = 'Using a secure messenger one-time security code';
$string['warning:otpsendstatus'] = 'The SMS gateway returned {$a}, but a verification code may still have been sent. Enter the code if you received it, or check the selected gateway settings if no code arrives.';
