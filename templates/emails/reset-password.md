---
subject: Your login details for {sitename}!
---
Dear {username},

You have requested to reset your password on {sitename} because you have forgotten your password.
If you did not request this, please ignore it.

To reset your password, please visit the following page:<br>
<a href="{url}/accounts/new-password/{username}/{new_hash}" style="color:#333; text-decoration:underline;">{url}/accounts/new-password/{username}/{new_hash}</a>

When you visit that page, your password will be reset, and the new password will be emailed to you.

Your username is: {username}

To edit your profile, go to this page:<br>
<a href="{url}/accounts/profile/{username}" style="color:#333; text-decoration:underline;">{url}/accounts/profile/{username}</a>

All the best,<br>
{sitename}
