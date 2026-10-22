# Week 1 Demo - SQL Injection to Remote Code Execution

SeaShaw with two doors left open, built for the 30-minute live demo in
*The Adnin Times: Offensive Security Careers (Part 1)*.

It is one site, not two labs. The first bug hands you the administrator's
password; the second is waiting behind the administrator's login. Slide 11's
case file - recon, research, access, impact - is the shape of the walkthrough.

## The chain

| # | Bug | What it gives you | Slide |
|---|-----|-------------------|-------|
| 1 | SQL injection in the gallery search | Every username and password, in plain text | 12, BUG 02 |
| 2 | Unrestricted file upload on the avatar form | Your code running on the server | 12, BUG 03 |

The payoff is deliberately not a flag:

- **Confidentiality** - 42 real passwords on screen, nothing to crack.
- **Integrity** - the homepage replaced with whatever you want.
- **Persistence** - the sign-in page replaced with one that writes every
  password down as members type it, and a drop site that fills while you watch.

## Run it

```
docker compose up -d --build
```

Then <http://localhost:8200>. First build takes a few minutes (it bakes the
database into the image); after that a container is serving in about two
seconds.

## Put it on the droplet

```
scp -r demo root@YOUR_DROPLET:/opt/week1-demo
ssh root@YOUR_DROPLET 'cd /opt/week1-demo && docker compose up -d --build'
```

It listens on 8200 and is self-contained - one container, its own database,
no dependency on the SeaShaw labs. Nothing in this folder touches the
existing SeaShaw deployment or its Caddy/HTTPS setup.

To serve it over HTTPS alongside the existing labs, add one block to the
Caddyfile already on the box, pointing a hostname at `127.0.0.1:8200`, and
bind the port to loopback in `docker-compose.yml`.

### What it needs

Measured with 41 sandboxes live and the bot signing in to every one of them:

| | At rest | 40 students browsing |
|---|---|---|
| RAM | 109 MB | 128 MB |
| CPU | ~0 | ~0.35 of a core at one click each per 4s |
| Page latency | 6 ms | 6 ms |

One request costs about 21 ms, so a single core serves ~46 of them a second -
roughly four times what forty students clicking can generate. Disk is 1 GB for
the image and about half a megabyte for forty sandboxes.

- **Alongside the existing SeaShaw droplet** (4 GB / 2 vCPU, ~1.2 GB at rest):
  adding this changes nothing. Just check there is ~2 GB of disk free.
- **On its own droplet:** the $6 `s-1vcpu-1gb` genuinely runs it. Take
  `s-1vcpu-2gb` if you will build the image on the box rather than copying it
  up, since the build installs and seeds MariaDB.

### If somebody wrecks it

A student with the web shell is `www-data`. They cannot touch the application
- it is owned by root and read-only to the web server, so the site itself
cannot be rewritten for everybody. They **can** reach the database, which is
shared, and `DROP TABLE users` would break the first half of the demo for the
room.

Permissions do not fix that: the application legitimately needs to write to
those tables, so any grant that lets the site work lets a shell wreck it.
Speed fixes it instead. Because the seeded data directory is baked into the
image, this is a one-second repair:

```
docker compose up -d --force-recreate
```

Tested: dropped the `users` table, the site started throwing SQL errors, and
that command had it serving 42 rows again in **1 second**. It also wipes every
sandbox, so everyone restarts - which mid-class is usually what you want
anyway.

## Accounts

| Account | Password | Note |
|---------|----------|------|
| `ahmad.faiz` | `student123` | Handed out on the objective strip. An ordinary student. |
| `admin` | *not published* | The point of the first half. Read it out of the database. |

The administrator's password is in `sql/01-schema.sql` if you need it in a
hurry mid-demo.

## Everyone gets their own copy

Forty students on one box cannot share one defaced homepage - the first
person to do it spoils it for everybody else.

So every browser gets a sandbox directory of its own under `i/<id>/`, keyed
to a cookie. Uploads, the web shell, the defacement and the captured
passwords all live there. What one student does is invisible to the rest,
and everybody arrives at a clean site. **RESET** in the top strip wipes a
sandbox and starts it over; **RESTORE HOMEPAGE** undoes only a defacement.

Your projector is just another browser, so walking the demo first spoils
nothing.

## The members who keep logging in

`bot/bot.php` signs in as a random member against every active sandbox every
three seconds, using that member's real password, from a plausible campus IP.

While the sign-in page is honest this is invisible. The moment a student
replaces it with one that writes passwords down, the same ordinary traffic
starts landing in their drop site - about twenty credentials a minute. That
is the whole argument for why this bug is worse than defacement, and it makes
it rather than asserting it.

## Deliberately insecure

Beyond the two bugs being taught:

- Passwords are stored exactly as typed. Hashing would not have stopped the
  injection; it would have stopped it from mattering. That is the lesson, and
  it only works if they are readable.
- Database errors are printed to the page.
- The sandbox root is writable by the web server, which is what makes an
  uploaded `.php` file executable. The application itself is read-only to the
  web server, so a shell cannot rewrite the site for everybody - but it can
  read other sandboxes and reach the shared database. Fine for a classroom,
  not fine anywhere else. See **If somebody wrecks it** above.

Do not put this on a network you care about, and do not reuse any of it.

## Layout

```
Dockerfile              one container: Apache + PHP + MariaDB
docker-compose.yml      builds and runs it on 8200
entrypoint.sh           database, then the bot, then Apache
sql/                    schema and seed - passwords in plain text
src/                    the application
  instance.php          the per-browser sandbox
  guide_data.php        the 23-step guided walkthrough
  login.tpl.php         the sign-in page each sandbox gets a copy of
  captured.php          the drop site
bot/bot.php             the members who keep logging in
payloads/               the two files a student uploads - shell.php, login.php
uploads/                seeded photos, outside the web root
RUNSHEET.md             the 30 minutes, in order
```

`payloads/` is the answer key. It is in this folder for you, not served to
anybody - keep it off the droplet if you would rather students typed them.
