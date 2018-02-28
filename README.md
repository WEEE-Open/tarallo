# T.A.R.A.L.L.O.
Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno

Server in PHP e (My)SQL (anche se forse un NoSQL sarebbe stato più adatto) del programma che useremo per gestire l'inventario dei vecchi computer accatastati in laboratorio, nonché di quelli donati ad associazioni e scuole a cui facciamo assistenza.

## Database
La struttura è in `database.sql`. Dopo una serie di false partenze, la scrittura di una spec non particolarmente formale e di due diagrammi entity-relationship, siamo pervenuti a questo.

In `database-data.sql` ci sono dei dati "statici" necessari al funzionamento del programma.

In `database-procedures.sql` ci sono un po' di trigger e procedure altrettanto necessarie

## Installazione

**WIP**

`composer install --no-dev -o` dovrebbe generare l'autoloader ottimizzato senza dipendenze di sviluppo (= PHPUnit).

## Gettext

Sarebbe bellissimo [se non richiedesse di installare e attivare sul server tutte le lingue, locali, localizzazioni,
translitterazioni fonetiche e che altro ne so](https://stackoverflow.com/questions/15541747/use-php-gettext-without-having-to-install-locales)
per poter leggere il file tradotto da me con le mie mani e incluso in questo repo. C'è `php-gettext` ma non riesco a
installarlo quindi resta tutto non tradotto.

In teoria per generare i .po e .mo bastano questi comandi, ma tanto non sto nemmeno più traducendo le stringhe:

`xgettext --from-code=UTF-8 -o SSRv1/locale/it_IT/LC_MESSAGES/tarallo.pot **/*.php`

`msgfmt tarallo.pot -o tarallo.mo`
