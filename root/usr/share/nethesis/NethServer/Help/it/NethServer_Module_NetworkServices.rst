================
Servizi di rete
================

La tabella mostra tutti i servizi di rete che girano localmente sul server.

Ogni servizio può avere più porte TCP/UDP aperte.
Le porte sono aperte sul firewall rispettando la proprietà `accesso`.
Tale proprietà può avere tre valori:

* localhost: il servizio è accessibile solo dal server stesso
* green: il servizio è accessibile solo dalle interfacce green e dalle reti fidate
* green red: il servizio è accessibile dalle reti green e red, ma non dalle reti blue e orange
* custom: il servizio ha accesso personalizzato configurato usando `Consenti host` o `Blocca host`


Quando l'accesso è configurato come pubblico o privato, l'amministratore può
specificare una lista di di host il cui accesso al servizio è sempre consentito (o negato).

Modifica
========

Modifica l'accesso ad un servizio di rete.

Accesso dalle reti green e red
    Selezionare se il servizio deve essere accessibile da tutte le reti, incluso Internet.
    Esempio: il server di posta deve essere accessibile da chiunque.

Accesso solo dalle reti green
    Selezionare se il servizio deve essere accessibile solo dalle reti locali.
    Esempio: un server database dovrebbe essere accessibile solo dalla LAN.

Accesso solo dal server stesso
    Selezionare se il servizio deve essere accessibile solo dal server stesso (localhost).
    Esempio: in una macchina virtuale pubblica (VPS) l'accesso al demone LDAP dovrebbe essere disabilitato da qualsiasi rete.

Consenti host
    Inserire una lista di indirizzi IP, o reti in formato CIDR, separati da virgole. Gli host elencati potranno sempre accedere
    al servizio di rete. (Si applica solo se l'accesso è pubblico o privato)

Blocca host
    Inserire una lista di indirizzi IP, o reti in formato CIDR, separati da virgole. Gli host elencati non potranno mai accedere
    al servizio di rete. (Si applica solo se l'accesso è pubblico o privato)


