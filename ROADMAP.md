# Proyecto TPV — Guía de desarrollo

## El proyecto

Un **TPV (Terminal Punto de Venta)** es el sistema que usan los negocios de hostelería para gestionar su operativa diaria: las mesas de los salones, los productos del menú y el registro de las ventas. Si alguna vez has visto a un camarero usar una tableta para anotar un pedido o imprimir una cuenta, estabas viendo un TPV en acción.

El objetivo de este proyecto es que construyas uno desde cero. Tendrás que diseñar cómo se ve, decidir cómo se organiza por dentro y hacer que funcione de forma estable. El sistema incluirá un área de configuración para administrar el negocio y una interfaz de venta para el uso diario del personal.

No esperamos que llegues a todo. Lo que buscamos es ver cómo trabajas, cómo evolucionas y qué decisiones técnicas tomas a lo largo del camino. 

Para arrancar el entorno, consulta el `README.md`. El modelo de datos completo está en [`DATA_MODEL.md`](./DATA_MODEL.md).

---

## Criterio propio sobre el modelo y las funcionalidades

El modelo de datos y las funcionalidades descritas en este documento son un punto de partida, no una especificación inamovible. Si durante el desarrollo detectas una inconsistencia, un campo que falta, una regla de negocio que no tiene sentido o una forma mejor de resolver algo, tu criterio prevalece sobre el texto. Documenta la decisión y justifícala brevemente: qué detectaste, por qué no seguiste el documento al pie de la letra y qué hiciste en su lugar. 

---

## Hitos

El proyecto se divide en hitos progresivos. Los primeros son obligatorios. Los últimos son opcionales y te permiten demostrar mayor profundidad.

Se valora más un hito bien terminado que varios a medias.

---

### Hito 1 — Modelo de datos
**Obligatorio**

Creación del esquema de base de datos y datos de prueba del sistema.

- Migraciones para todas las entidades definidas en [`DATA_MODEL.md`](./DATA_MODEL.md)
- Seeders con datos suficientes para poder trabajar sin introducirlos a mano
- Relaciones correctas entre entidades y soft deletes donde corresponda

---

### Hito 2 — API REST: Backoffice
**Obligatorio**

Endpoints para gestionar la configuración del sistema.

- Autenticación de usuarios
- CRUD completo para: familias, impuestos, productos, zonas y mesas
- Activar y desactivar productos y familias
- Validaciones y respuestas de error coherentes

---

### Hito 3 — Interfaz: Backoffice
**Obligatorio**

Interfaz de administración para gestionar la configuración del sistema.

- Diseño previo de las pantallas principales antes de implementarlas
- Vistas para gestionar todas las entidades del backoffice
- Formularios con validación y navegación clara entre secciones

---

### Hito 4 — Front de venta (TPV)
**Obligatorio**

Interfaz principal de venta para el uso diario del personal del local.

**Qué se pide:**

**Vista principal**
- Mostrar las zonas del establecimiento y las mesas de cada zona
- Indicar el estado de cada mesa mediante color (libre / ocupada)
- Permitir cambiar entre zonas fácilmente

**Apertura de venta**
- Al seleccionar una mesa libre, elegir el usuario que realiza la operación
- Registrar el número de comensales mediante teclado numérico (pensado para uso táctil)
- Registrar usuario, fecha de apertura y número de comensales; la mesa pasa a ocupada

**Gestión de venta**
- Productos agrupados por familia, con posibilidad de añadir, modificar cantidad y eliminar líneas
- Cada línea registra el precio e impuesto en el momento de la venta y el usuario que la añade
- Posibilidad de editar comensales e imprimir ticket provisional


**Cierre de venta**
- Solicitar el usuario que cierra, calcular el total, asignar número de ticket y liberar la mesa

---

### Hito 5 — Informes
**Opcional**

Consulta y visualización de la actividad de ventas del sistema.


- Listado de ventas con filtro por rango de fechas: número de ticket, mesa, usuarios de apertura y cierre, fechas y total
- Detalle de las líneas de cada venta
- Ventas agrupadas por día, zona, producto y usuario
- Al menos una gráfica de evolución o distribución de ventas

---

### Hito 6 — Mejoras
**Opcional**

Ampliaciones funcionales o técnicas del sistema. Puedes elegir las que más te interesen, proponer las tuyas o combinar ambas.

#### Mejoras funcionales

- **Roles de usuario** — diferenciar entre perfiles con distintas capacidades: por ejemplo, un administrador que gestiona el sistema, un supervisor que accede a informes y un operador que solo usa el front de venta. Cada rol vería y podría hacer cosas distintas
- **Autenticación por PIN** — solicitar PIN de usuario al realizar operaciones en el TPV, en lugar de selección por lista
- **División de cuenta** — calcular cuánto corresponde a cada comensal y reflejarlo en el ticket
- **Métodos de pago** — registrar pagos en efectivo, tarjeta u otros métodos, incluyendo pago mixto entre varios
- **Cierre de caja** — registro del cierre diario con el total de ventas del día y desglose por método de pago
- **Traslado de mesa** — mover una venta abierta de una mesa a otra
- **Gestión de devoluciones** — anular líneas o ventas ya cerradas y reflejar el impacto correctamente
- **Descuentos** — aplicar descuentos por línea o sobre el total de la venta
- **Diseño interactivo del salón** — colocar mesas en un plano con su posición y tamaño reales
- **Integración con impresoras de tickets** — impresión real del ticket al cierre de venta
- **Generación de imágenes de productos** — sugerir imágenes automáticamente al crear un producto usando un servicio externo
- **Personalización de familias** - poder añadir distintivos visuales a las familias (imagen, color, icono...) para facilitar su identificación en el TPV

#### Mejoras técnicas

- **Autenticación basada en tokens** — reemplazar la autenticación por sesión por un esquema stateless mediante tokens firmados, más adecuado para una API consumida por un cliente desacoplado

- **Bus de eventos síncrono** — introducir un mecanismo de eventos dentro del ciclo de la petición para desacoplar efectos secundarios (como recalcular el total al añadir una línea) de la lógica principal del caso de uso

- **Bus de eventos asíncrono** — procesar en background efectos secundarios que no necesitan respuesta inmediata, como actualizaciones de stock o notificaciones, liberando la petición principal

- **Separación lectura/escritura** — diferenciar el modelo que escribe (lógica de dominio, casos de uso) del modelo que lee (consultas optimizadas para la interfaz). Especialmente útil para los informes, donde las necesidades de consulta son muy distintas a las de escritura

- **Validación en capa de dominio** — llevar las reglas de negocio a las propias entidades y value objects, de forma que sea imposible construir objetos en estado inválido, en lugar de depender únicamente de la validación en la entrada HTTP

- **Actualizaciones en tiempo real** — mantener el estado de las mesas sincronizado entre varios terminales sin necesidad de recargar la página

- **Registro de auditoría** — trazar las operaciones sensibles del sistema (quién abrió, modificó o cerró una venta y cuándo) de forma que quede un histórico inmutable

---

## Qué se evalúa - el orden no importa

| Aspecto | Qué se mira |
|---|---|
| **Calidad del código** | Que sea claro, coherente y fácil de modificar |
| **Arquitectura** | Que las decisiones de diseño estén justificadas y el sistema sea fácil de extender |
| **Git** | Commits descriptivos, organización en ramas, histórico que cuente la evolución real del proyecto |
| **Interfaz** | Usabilidad, coherencia visual y adecuación al uso real de un TPV |
| **Funcionalidad** | Que lo implementado funcione de forma estable y completa |
| **Autonomía** | Capacidad de resolver problemas y tomar decisiones sin necesidad de que te indiquen cómo |

**Sobre el uso de asistentes de IA:** está permitido y es una herramienta habitual en el equipo. Se valorará que lo uses de forma responsable: tomando decisiones propias y entendiendo el código que incorporas, no como sustituto del razonamiento técnico.
