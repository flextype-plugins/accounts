---
subject: Your login details for {sitename}!
---
Dear {user},<br><br>

You have requested to reset your password on {sitename} because you have forgotten your password.
If you did not request this, please ignore it.<br><br>

To reset your password, please visit the following page:<br>
<a href="{url}/accounts/new-password/{email}/{new_hash}" style="color:#333; text-decoration:underline;">{url}/accounts/new-password/{email}/{new_hash}</a><br><br>

When you visit that page, your password will be reset, and the new password will be emailed to you.<br><br>

Your email is: {email}<br><br>

To edit your profile, go to this page:<br>
<a href="{url}/accounts/profile/{email}/edit" style="color:#333; text-decoration:underline;">{url}/accounts/profile/{email}/edit</a><br><br>

All the best,<br>
{sitename}
