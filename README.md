# CyberPhish

Learn to spot phishing by facing it. CyberPhish is a free online course that
takes you from short lessons to realistic phishing scenarios, where every
choice has consequences, and ends with a test and a certificate with your name
on it. It speaks five languages: English, Lithuanian, Latvian, Greek and
Estonian.

Try it at [cyberphish.knf.vu.lt](https://cyberphish.knf.vu.lt), hosted by
Vilnius University Kaunas Faculty. CyberPhish was funded with support from the
European Commission (Erasmus+).

<br/>

## How the course works

1. **Learn.** Lessons come grouped into topics, with files and PDFs to read
   along. Mark a lesson done and your progress bar fills up.
2. **Check yourself.** Once a topic's lessons are done, a short
   self-evaluation shows what stuck, and which answers were right.
3. **Practise.** Phishing simulations put you in a realistic situation and ask
   what you would do, one step at a time. Good choices score points. Learning
   mode gives feedback after every step; testing mode saves it for the end.
4. **Prove it.** With every lesson done, the knowledge test draws questions
   from all the topics. Score 75% or more, within three tries, and download
   your certificate.

Badges along the way and public rankings for the self-evaluations and the
simulations keep it playful.

<br/>

## For course organisers

Everything the course shows is managed in the admin panel at `/admin-panel`:
the lessons and their files, the self-evaluation and knowledge-test questions
(with a plain-text import), the simulations (imported from a spreadsheet),
news and information pages. Organisers follow each learner's progress,
self-evaluations, simulation runs and test results. Country managers get the
same view, limited to the learners of their own country.

<br/>

## Run it yourself

You need Docker with Compose and a domain name. CyberPhish listens on port 80
and expects an HTTPS reverse proxy in front of it, the way it runs at
cyberphish.knf.vu.lt. For sign-up to work you also need Google reCAPTCHA v2
keys and an SMTP account for the verification mails.

```sh
cp docker-compose.sample.yml docker-compose.yml
cp .env.example .env
# docker-compose.yml: set APP_URL, APP_URL_ADMIN and MAIL_FROM_ADDRESS to your address
# .env: fill in the passwords and keys
./runUpdateThisStack.sh
```

A few of the keys in `.env` need a word:

- `LARAVEL_APP_KEY` is `base64:` followed by 32 random bytes:
  `echo "base64:$(openssl rand -base64 32)"`. `LARAVEL_APP_SALT` can be any
  random string.
- The sample sends mail through Mailjet; for another SMTP server, change the
  `MAIL_` lines in `docker-compose.yml`. Just trying it out? Set
  `MAIL_MAILER: log` and the sign-up mails, verification link included, land
  in `_LOGS/apache/laravel_logs/laravel.log`.
- `ADOBE_EMBED_API` is optional: an Adobe PDF Embed key shows PDF lessons
  inside the page.

On the first start MySQL builds the database from `mysql/init.sql` with one
main admin whose password nobody knows. Give it your address and a password
before you sign in at `/admin-panel`:

```sh
HASH=$(docker exec cyberphish-apache php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT);' 'your-new-password')
docker exec -i cyberphish-mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' <<SQL
UPDATE bei_managers SET email = 'you@example.org', password = '$HASH' WHERE id = 1;
SQL
```

A fresh install starts empty, with English as its only language. Add the
other languages in the panel (their translations ship with the app under the
codes `lt`, `lv`, `gr` and `ee`), then your lessons, questions and
simulations, and the course is ready for learners.

<br/>

## What's inside

| Service | What it does |
|---|---|
| `cyberphish-endpoint` | Caddy on port 80: security headers, `/static` to the static files, `/dbgate` to the database browser, everything else to the app |
| `cyberphish-apache` | The course site and the admin panel: Laravel on PHP 8.4 under Apache |
| `cyberphish-static` | The shared stylesheets, fonts and flag icons under `/static` |
| `cyberphish-mysql` | MySQL 5.7, created from `mysql/init.sql` on the first start |
| `cyberphish-redis` | Redis, configured for the app, which keeps its sessions and cache in files for now |
| `cyberphish-dbback-st`, `cyberphish-dbback-lt` | Database backups at 03:00: every night, keeping 20, and every Saturday, keeping 60 |
| `cyberphish-dbgate` | Database browser at `/dbgate` (user `admin`, password in `.env`) |

The database, the uploads and the backups live in `_DATA/`, the logs in
`_LOGS/`.

<br/>

## Working on it

The sample runs the published Docker Hub image `vuknf/cyberphish-apache`. To
change the code, switch `cyberphish-apache` to the self-built variant
commented in `docker-compose.sample.yml`.

`apache/runTests.sh` builds the production image and runs the regression
suite in a throwaway container: the image as shipped, the app driven
in-process, and real requests through Apache. `apache/app/tests/README.md`
explains how the suites are laid out.
