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
