================
Servizi di rete
================

La tabella mostra tutti i servizi di rete che girano localmente sul server.

Ogni servizio può avere più porte TCP/UDP aperte.
Le porte sono aperte sul firewall rispettando la proprietà `accesso`.
Tale proprietà può avere tre valori:

* privato: il servizio è accessibile solo dalle reti locali (es. interfacce verdi)
* pubblico: il servizio è accessibile da tutte le reti, compreso Internet
* nessuno: il servizio è accessibile solo dal server stesso (localhost)

Quando l'accesso è configurato come pubblico o privato, l'amministratore può
specificare una lista di di host il cui accesso al servizio è sempre consentito (o negato).

Modifica
========

Modifica l'accesso ad un servizio di rete.

Accesso solo dalle reti locali (privato)
    Selezionare se il servizio deve essere accessibile solo dalle reti locali.
    Esempio: un server database dovrebbe essere accessibile solo dalla LAN.

Accesso da tutte le reti (pubblico)
    Selezionare se il servizio deve essere accessibile da tutte le reti, incluso Internet.
    Esempio: il server di posta deve essere accessibile da chiunque.

Accesso solo dal server stesso (nessuno)
    Selezionare se il servizio deve essere accessibile solo dal server stesso (localhost).
    Esempio: in una macchina virtuale pubblica (VPS) l'accesso al demone LDAP dovrebbe essere disabilitato da qualsiasi rete.

Consenti host
    Inserire una lista di indirizzi IP separati da virgole. Gli host elencati potranno sempre accedere
    al servizio di rete. (Si applica solo se l'accesso è pubblico o privato)

Blocca host
    Inserire una lista di indirizzi IP separati da virgole. Gli host elencati non potranno mai accedere
    al servizio di rete. (Si applica solo se l'accesso è pubblico o privato)


