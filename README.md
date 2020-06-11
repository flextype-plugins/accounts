<h1 align="center">Accounts Plugin for <a href="http://flextype.org/">Flextype</a></h1>

<p align="center">
<a href="https://github.com/flextype-plugins/accounts/releases"><img alt="Version" src="https://img.shields.io/github/release/flextype-plugins/accounts.svg?label=version&color=black"></a> <a href="https://github.com/flextype-plugins/accounts"><img src="https://img.shields.io/badge/license-MIT-blue.svg?color=black" alt="License"></a> <a href="https://github.com/flextype-plugins/accounts"><img src="https://img.shields.io/github/downloads/flextype-plugins/accounts/total.svg?color=black" alt="Total downloads"></a> <a href="https://github.com/flextype/flextype"><img src="https://img.shields.io/badge/Flextype-0.9.8-green.svg" alt="Flextype"></a> <a href=""><img src="https://img.shields.io/discord/423097982498635778.svg?logo=discord&color=black&label=Discord%20Chat" alt="Discord"></a>
</p>

## Dependencies

The following dependencies need to be downloaded and installed for Accounts Plugin.

| Item | Version | Download |
|---|---|---|
| [flextype](https://github.com/flextype/flextype) | 0.9.8 | [download](https://github.com/flextype/flextype/releases) |
| [site](https://github.com/flextype-plugins/site) | >=1.0.0 | [download](https://github.com/flextype-plugins/site/releases) |
| [twig](https://github.com/flextype-plugins/twig) | >=1.0.0 | [download](https://github.com/flextype-plugins/twig/releases) |

## Installation

1. Download & Install all required dependencies.
2. Create new folder `/project/plugins/accounts`
3. Download Accounts Plugin and unzip plugin content to the folder `/project/plugins/accounts`

## Documentation

### Settings

| Key | Value | Description |
|---|---|---|
| enabled | true | true or false to disable the plugin |
| priority | 80 | accounts plugin priority |

### Restrict access in the entries frontmatter

You may restrict access for specific users to your entry(entries) in the entry(entries) frontmatter.

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
access:
  accounts:
    roles:
      - student
      - admin
    usernames:
      - jack
      - sam
    uuids:
      - ea7432a3-b2d5-4b04-b31d-1c5acc7a55e2
      - d549af27-79a0-44f2-b9b1-e82b47bf87e2
---
Lesson content is here...
```

### Restrict access in the entries content and in any other entry custom field.

You may restrict access for specific users to your specific content inside the entry by using shortcodes.

#### Show private content for logged in users

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
---
Public text here...

[userLoggedIn]
    Lesson content is here...
[/userLoggedIn]
```

#### Show private content for users with roles: admin and student

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
---
Public text here...

[userLoggedInRoleOneOf roles="admin, student"]
    Private content here..
[/userLoggedInRoleOneOf]
```

#### Show private content for users with uuids ea7432a3-b2d5-4b04-b31d-1c5acc7a55e2 and d549af27-79a0-44f2-b9b1-e82b47bf87e2

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
---
Public text here...

[userLoggedInUuidOneOf uuids="ea7432a3-b2d5-4b04-b31d-1c5acc7a55e2, d549af27-79a0-44f2-b9b1-e82b47bf87e2"]
    Private content here..
[/userLoggedInUuidOneOf]
```

#### Show private content for users with usernames jack, sam

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
---
Public text here...

[userLoggedInUsernameOneOf usernames="jack, sam"]
    Private content here..
[/userLoggedInUsernameOneOf]
```

### Show logged in username

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
---

Hello [userLoggedInUsername]
```

### Show logged in uuid

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
---

Hello [userLoggedInUsername], your uuid: [userLoggedInUuid]
```


### Show logged in role

`/project/entries/lessons/lesson-42.md`

```
---
title: Lesson 42
---

Hello [userLoggedInUsername], your uuid: [userLoggedInUuid] and your role: [userLoggedInRole]
```

### Also you may use any of this shortcodes inside any entry fields:

Example:

```
---
title: [userLoggedIn][userLoggedInUsername] - [/userLoggedIn]Lesson 42
---
Public text here...

[userLoggedIn]
    Private content here..
[/userLoggedIn]
```

### Restrict access in the twig templates.


## LICENSE
[The MIT License (MIT)](https://github.com/flextype-plugins/accounts/blob/master/LICENSE.txt)
Copyright (c) 2020 [Sergey Romanenko](https://github.com/Awilum)
