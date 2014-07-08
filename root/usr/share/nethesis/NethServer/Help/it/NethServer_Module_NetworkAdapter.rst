====
Rete
====

Cambia impostazioni delle interfacce di rete. Le interfacce di rete presenti nel sistema sono rilevate automaticamente.

Stato
=====

Link
    Indica se la scheda è collegata a qualche apparato di rete (ad es. cavo
    ethernet collegato allo switch aziendale).

Modello
    Modello della scheda di rete utilizzata.

Velocità
    Indica la velocità che la scheda di rete ha negoziato (espressa in Mb/s).

Driver
    Il Driver che il sistema utilizza per pilotare la scheda.

Bus
    Su quale bus è collegata la scheda di rete (es: pci, usb).



Modifica
========

Modifica le impostazioni dell'interfaccia di rete

Scheda
    Nome dell'interfaccia di rete. Questo campo non può essere
    modificato.

Indirizzo MAC
    Indirizzo fisico della scheda di rete. Questo campo non può essere
    modificato.

Ruolo
    Il ruolo indica la destinazione d'uso dell'interfaccia, ad esempio:

    * Green: LAN locale
    * Blue: rete ospiti
    * Orange: rete DMZ
    * Red: Internet, IP pubblico
    
Modalità
    Indica quale metodo verrà usato per attribuire l'indirizzo IP alla
    scheda di rete, valori i possibili sono *Statico* e *DHCP*.

Statico
    La configurazione è attribuita staticamente.

    * Indirizzo IP: indirizzo IP della scheda di rete
    * Netmask: netmask della scheda di rete
    * Gateway: default gateway del server

DHCP
    La configurazione è attribuita dinamicamente (disponibile solo per interfacce
    RED)

Interfacce logiche
    Le interfacce logiche sono speciali configurazioni di rete. I tipi supportati sono:

    * Alias: associa uno o più IP ad una scheda esistenza. L'aloas ha lo stesso ruolo della scheda fisica associata
    * Bond: combina due o più interfacce, arantisce bilanciamento del traffico e tolleranza ai guasti
    * Bridge: collega due reti distinte, è spesso utilizzata per le VPN in bridge e le macchine virtuali
    * VLAN (Virtual Local Area Network): crea due o più reti fisicamente separate usando una singola interfaccia

