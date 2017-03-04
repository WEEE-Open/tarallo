# T.A.R.A.L.L.O.
Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno

Server in PHP e (My)SQL (anche se forse un NoSQL sarebbe stato più adatto) del programma che useremo per gestire l'inventario dei vecchi computer accatastati in laboratorio, nonché di quelli donati ad associazioni e scuole a cui facciamo assistenza.

## Database
La struttura è in `database.sql`. Dopo una serie di false partenze, la scrittura di una spec non particolarmente formale e di due diagrammi entity-relationship, siamo pervenuti a questo.

### TODO
- [x] Foreign key
- [ ] Qualcosa per rappresentare la struttura ad albero degli Item
- [ ] Gestire proprietà duplicate con valori diversi
- [x] Trovare una soluzione più degna per tenere traccia di che stanno facendo gli adesivi col numero di inventario e col seriale di Windows (è necessario tenerne traccia perché dobbiamo staccarli e restituirli prima di dare via i computer)
