===
Red
===

Cambiar la configuración de las interfaces de red. Las interfaces de red del sistema se detectan automáticamente.

Estado
======

Enlace
    Indica si el adaptador está conectado a cualquier dispositivo de red (por ejemplo, Ethernet cable conectado al interruptor)

Modelo
    Modelo de la tarjeta de red utilizada.

Velocidad
    Indica la velocidad que la tarjeta de red ha negociado (expresada en Mb/s). 

Driver
    El driver utiliza el sistema para controlar la tarjeta.

Bus
    Tarjeta de red física del bus (por ejemplo, PCI, USB).

Editar
======

Cambiar la configuración de la interfaz de red 

Tarjeta
    Nombre de la interfaz de red. Este campo no puede estar cambiado. 

Dirección MAC
    Dirección física de la tarjeta de red. Este campo no puede estar cambiado.

Rol
    El rol indica el destino de uso de la interfaz, por ejemplo:

    * Verde -> Negocios LAN
    * Rojo -> Internet, IP pública 

Modo
    Indica qué método se utilizará para asignar la dirección IP a el adaptador de red. Los valores posibles son *Estático* y *DHCP*.

Estático
    La configuración se reserva estáticamente.

    * Dirección IP: dirección IP de la tarjeta de red
    * Máscara de red: máscara de red de la tarjeta de red 
    * Puerta de acceso: Servidor de puerta de enlace predeterminada

DHCP
    La  configuración se asigna dinámicamente (sólo disponible para Interfaces de RED)
