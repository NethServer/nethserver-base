===============
Software center
===============

Disponibili
===========

La scheda :guilabel:`Disponibili` consente di selezionare da una lista
i moduli e i pacchetti opzionali da installare.  Premendo il pulsante
rosso :guilabel:`Aggiungi` viene richiesta conferma, prima di avviare
il processo di installazione.

La lista può essere filtrata per categoria, premendo i pulsanti che le
stanno al di sopra.  La categoria speciale :guilabel:`Tutto` mostra la
lista completa.

.. NOTE::
   
   1. Sia i moduli che le categorie sono definiti dai metadati di YUM.
   2. I pacchetti opzionali possono essere installati anche *dopo*
      l'installazione del relativo modulo, dalla pagina
      :guilabel:`Installed`.

   
Installati
==========

Elenca i moduli installati sul sistema.  I moduli sono ordinati
alfabeticamente.  Su ognuno di essi è possibile effettuare le seguenti
azioni:

Rimuovi

    Il pulsante :guilabel:`Remove` rimuove il modulo relativo,
    dopo aver richiesto conferma.

Modifica

    Il pulsante :guilabel:`Modifica` mostra l'elenco dei pacchetti
    opzionali associati al modulo. Premendo :guilabel:`Applica
    modifiche`, gli elementi selezionati vengono installati, mentre
    quelli non selezionati sono rimossi.


Pacchetti
---------

Elenca i pacchetti installati sul sistema, ordinati per nome.

Name
    Nome del pacchetto.

Versione
    Versione del pacchetto.

Release
    Numero di rilascio e codice della distribuzione del pacchetto.


Aggiornamenti
=============

Questa pagina elenca gli aggiornamenti disponibili per i pacchetti
installati.  La lista viene aggiornata periodicamente.

Maggiori informazioni sugli aggiornamenti sono mostrati cliccando su
:guilabel:`CHANGELOG degli aggiornamenti`.

Premendo :guilabel:`Scarica e installa` i pacchetti elencati vengono
aggiornati.


