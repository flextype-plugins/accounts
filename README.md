<h1 align="center">Accounts Plugin for <a href="https://flextype.org/">Flextype</a></h1>

<p align="center">
<a href="https://github.com/flextype-plugins/accounts/releases"><img alt="Version" src="https://img.shields.io/github/release/flextype-plugins/accounts.svg?label=version&color=black"></a> <a href="https://github.com/flextype-plugins/accounts"><img src="https://img.shields.io/badge/license-MIT-blue.svg?color=black" alt="License"></a> <a href="https://github.com/flextype-plugins/accounts"><img src="https://img.shields.io/github/downloads/flextype-plugins/accounts/total.svg?color=black" alt="Total downloads"></a> <a href="https://github.com/flextype/flextype"><img src="https://img.shields.io/badge/Flextype-0.9.16-green.svg" alt="Flextype"></a> <a title="Crowdin" target="_blank" href="https://crowdin.com/project/flextype-plugin-accounts"><img src="https://badges.crowdin.net/flextype-plugin-accounts/localized.svg"></a> <a href="https://flextype.org/discord"><img src="https://img.shields.io/discord/423097982498635778.svg?logo=discord&color=black&label=Discord%20Chat" alt="Discord"></a>
</p>

Accounts Plugin to manage users accounts in Flextype.  
Built in predesigned and fully customizable pages: Accounts List, Login, Registration, Password Reset, Account Profile and Account Edit Profile.

### Dependencies

The following dependencies need to be downloaded and installed for Accounts Plugin.

| Item | Version | Download |
|---|---|---|
| [flextype](https://github.com/flextype/flextype) | 0.9.16 | [download](https://github.com/flextype/flextype/releases) |
| [site](https://github.com/flextype-plugins/site) | >=1.0.0 | [download](https://github.com/flextype-plugins/site/releases) |
| [twig](https://github.com/flextype-plugins/twig) | >=2.0.0 | [download](https://github.com/flextype-plugins/twig/releases) |
| [acl](https://github.com/flextype-plugins/acl) | >=1.0.0 | [download](https://github.com/flextype-plugins/acl/releases) |
| [phpmailer](https://github.com/flextype-plugins/phpmailer) | >=1.0.0 | [download](https://github.com/flextype-plugins/phpmailer/releases) |

### Installation

1. Download & Install all required dependencies.
2. Create new folder `/project/plugins/accounts`
3. Download Accounts Plugin and unzip plugin content to the folder `/project/plugins/accounts`
4. Copy all fieldsets from `/project/plugins/accounts/bluprints` to `/project/bluprints` folder.
5. Add new collection to `entries.collections` => `/project/config/flextype/settings.yaml` 
    
    ```yaml
    accounts:
      pattern: accounts
      filename: entry
      extension: yaml
      serializer: yaml
      fields: 
        registry:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/RegistryField.php"
        slug:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/SlugField.php"
        published_at:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/PublishedAtField.php"
        published_by:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/PublishedByField.php"
        modified_at:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/ModifiedAtField.php"
        created_at:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/CreatedAtField.php"
        created_by:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/CreatedByField.php"
        routable:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/RoutableField.php"
        parsers:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/ParsersField.php"
        visibility:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/VisibilityField.php"
        uuid:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/UuidField.php"
        id:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/IdField.php"
    accounts_item:
      pattern: accounts/([a-zA-Z0-9_-]+)
      filename: account
      extension: yaml
      serializer: yaml
      fields: 
        registry:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/RegistryField.php"
        slug:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/SlugField.php"
        published_at:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/PublishedAtField.php"
        published_by:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/PublishedByField.php"
        modified_at:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/ModifiedAtField.php"
        created_at:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/CreatedAtField.php"
        created_by:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/CreatedByField.php"
        routable:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/RoutableField.php"
        parsers:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/ParsersField.php"
        visibility:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/VisibilityField.php"
        uuid:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/UuidField.php"
        id:
          enabled: true
          path: "/src/flextype/core/Entries/Fields/Default/IdField.php"
    ```

### Resources

* [Documentation](https://flextype.org/downloads/extend/plugins/accounts)

### License
[The MIT License (MIT)](https://github.com/flextype-plugins/accounts/blob/master/LICENSE.txt)
Copyright (c) 2021 [Sergey Romanenko](https://github.com/Awilum)
