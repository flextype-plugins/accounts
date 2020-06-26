<h1 align="center">Accounts Plugin for <a href="http://flextype.org/">Flextype</a></h1>

<p align="center">
<a href="https://github.com/flextype-plugins/accounts/releases"><img alt="Version" src="https://img.shields.io/github/release/flextype-plugins/accounts.svg?label=version&color=black"></a> <a href="https://github.com/flextype-plugins/accounts"><img src="https://img.shields.io/badge/license-MIT-blue.svg?color=black" alt="License"></a> <a href="https://github.com/flextype-plugins/accounts"><img src="https://img.shields.io/github/downloads/flextype-plugins/accounts/total.svg?color=black" alt="Total downloads"></a> <a href="https://github.com/flextype/flextype"><img src="https://img.shields.io/badge/Flextype-0.9.8-green.svg" alt="Flextype"></a> <a href=""><img src="https://img.shields.io/discord/423097982498635778.svg?logo=discord&color=black&label=Discord%20Chat" alt="Discord"></a>
</p>

## Features

* Built in predesigned and fully customizable pages: Accounts List, Login, Registration, Password Reset, Account Profile and Account Edit Profile.

## Dependencies

The following dependencies need to be downloaded and installed for Accounts Plugin.

| Item | Version | Download |
|---|---|---|
| [flextype](https://github.com/flextype/flextype) | 0.9.8 | [download](https://github.com/flextype/flextype/releases) |
| [site](https://github.com/flextype-plugins/site) | >=1.0.0 | [download](https://github.com/flextype-plugins/site/releases) |
| [twig](https://github.com/flextype-plugins/twig) | >=1.0.0 | [download](https://github.com/flextype-plugins/twig/releases) |
| [acl](https://github.com/flextype-plugins/acl) | >=1.0.0 | [download](https://github.com/flextype-plugins/acl/releases) |
| [phpmailer](https://github.com/flextype-plugins/phpmailer) | >=1.0.0 | [download](https://github.com/flextype-plugins/phpmailer/releases) |

## Installation

1. Download & Install all required dependencies.
2. Create new folder `/project/plugins/accounts`
3. Download Accounts Plugin and unzip plugin content to the folder `/project/plugins/accounts`
4. Copy all fieldsets from `/project/plugins/accounts/fieldsets` to `/project/fieldsets` folder.

## Documentation

### Routes
| Method | Route | Route Name | Description |
|---|---|---|---|
| GET | /accounts | `accounts.index` | Page with a list of registered accounts |
| GET | /accounts/login | `accounts.login` | Login page |
| GET | /accounts/registration | `accounts.registration` | Registration page |
| GET | /accounts/reset-password | `accounts.resetPassword` | Reset password page |
| GET | /accounts/profile/{email} | `accounts.profile` | Profile page |
| GET | /accounts/profile/{email}/edit | `accounts.profileEdit` | Profile page |
| POST | /accounts/profile/{email}/edit | `accounts.profileEditProcess` | Profile edit process |
| POST | /accounts/registration | `accounts.profileRegistrationProcess` | Profile registration process |
| POST | /accounts/login | `accounts.profileLoginProcess` | Profile login process |
| POST | /accounts/logout | `accounts.profileLogoutProcess` | Profile login process |
| POST | /accounts/reset-password | `accounts.resetPasswordProcess` | Reset password process |
| POST | /accounts/new-password/{email}/{hash} | `accounts.newPasswordProcess` | New password process |

### Settings

| Key | Value | Description |
|---|---|---|
| enabled | true | true or false to disable the plugin |
| priority | 80 | Accounts plugin priority |
| from.email | support@flextype.org | From email |
| index.enabled | true | Allow accounts index `true` or `false` |
| registration.enabled | true | Allow user registration `true` or `false` |
| registration.default_roles | user | Default roles for new users |
| registration.default_state | enabled | Default state for new users |
| registration.redirect.route.name | accounts.login | Redirect route name after new user registered. |
| registration.redirect.route.link |  | Redirect link after new user registered. |
| login.enabled | true | Allow user login `true` or `false` |
| login.redirect.route.name | accounts.profile | Redirect route name after user logs in. |
| login.redirect.route.link | | Redirect link after user logs in. |
| profile.enabled | true | Allow user profile view `true` or `false` |
| profile_edit.enabled | true | Allow user profile edit `true` or `false` |
| profile_edit.redirect.route.name | accounts.profile | Redirect route name after user profile edited. |
| profile_edit.redirect.route.link | | Redirect link after user profile edited. |
| new_password.enabled | true | Allow user new password `true` or `false` |
| new_password.redirect.route.name | accounts.login | Redirect route name after new password was sended. |
| new_password.redirect.route.link | | Redirect link after new password was sended. |
| reset_password.enabled | true | Allow user reset password `true` or `false` |
| reset_password.redirect.route.name | accounts.login | Redirect route name after password was reset. |
| reset_password.redirect.route.link | | Redirect link after password was reset. |
| logout.redirect.route.name | accounts.login | Redirect route name after user logs out. |
| logout.redirect.route.link | | Redirect link after user logs out.|

### Events

| Event | Description |
|---|---|
| onAccountsUserLoggedIn | Allows plugins to include their own logic when user logs in. |
| onAccountsNewPasswordSended | Allows plugins to include their own logic when new password was sended. |
| onAccountsPasswordReset | Allows plugins to include their own logic when password was reset. |
| onAccountsNewUserRegistered | Allows plugins to include their own logic when new was user registered. |
| onAccountsProfileEdited | Allows plugins to include their own logic when user profile edited. |
| onAccountsLogout | Allows plugins to include their own logic when user logs out. |

## LICENSE
[The MIT License (MIT)](https://github.com/flextype-plugins/accounts/blob/master/LICENSE.txt)
Copyright (c) 2020 [Sergey Romanenko](https://github.com/Awilum)
