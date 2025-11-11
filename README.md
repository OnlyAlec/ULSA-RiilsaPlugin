<h1 align="center">RIILSA – Plugin WordPress</h1>
<p align="center">
  <img src="https://riilsa.org/wp-content/uploads/2024/11/RILLSA_FullColor-768x261.png" alt="LogoRiilsa" width="250px">
</p>
<p align="center">
  <img src="https://img.shields.io/badge/Versión-4.0.0-blue?style=for-the-badge" alt="Versión del Plugin">
  <img src="https://img.shields.io/badge/WordPress-6.8.2%2B-green?style=for-the-badge" alt="Versión de WordPress">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-blueviolet?style=for-the-badge" alt="Versión de PHP">
  <img src="https://img.shields.io/badge/Licencia-Propietaria-red?style=for-the-badge" alt="Licencia">
</p>

## ¿Qué es RIILSA Plugin?

El proyecto consiste de un plugin de WordPress diseñado para centralizar y extender las funcionalidades del sitio web de la Red Internacional de Investigación La Salle (RIILSA). Su objetivo principal es simplificar la gestión de contenido y la comunicación con la comunidad a través de herramientas automatizadas para _administradores_.

El plugin permite:

- **Gestionar contenido académico** (Proyectos, Convocatorias y Noticias) a partir de archivos Excel, automatizando la creación y actualización de entradas.
- **Construir y enviar boletines informativos** por correo electrónico, utilizando plantillas HTML optimizadas y segmentando las audiencias.

## Características Principales

### Gestor de Contenido Automatizado

- **Carga desde Excel**: Utiliza archivos `.xlsx` subidos a través de un widget de Elementor para gestionar el contenido.
- **Creación Automática de Entradas**: Transforma las filas del Excel en posts de WordPress, mapeando columnas a campos personalizados (ACF).
- **Manejo de Taxonomías**: Asigna categorías y etiquetas automáticamente a partir de los datos.
- **Procesamiento de Imágenes**: Descarga y asocia imágenes desde enlaces de Google Drive.
- **Gestión de Estados**: Controla la visibilidad del contenido basado en fechas de inicio y fin.

### Sistema de Boletines (Newsletter)

- **Selección Visual**: Interfaz intuitiva para elegir noticias y convocatorias a incluir en el boletín.
- **Generación de HTML**: Crea correos electrónicos responsivos a partir de plantillas MJML precompiladas.
- **Envío Masivo**: Utiliza la API de Brevo (antes Sendinblue) para garantizar una alta tasa de entrega.
- **Gestión de Suscriptores**: Formulario de suscripción con doble confirmación (opt-in) y listas segmentadas por dependencia.
- **Programación y Métricas**: Permite programar envíos y ofrece un seguimiento básico de aperturas y clics (próximamente).

### Seguridad y Buenas Prácticas

- **Protección CSRF**: Uso de nonces de WordPress para todas las peticiones AJAX.
- **Sanitización y Escape**: Limpieza de datos de entrada y escape de datos de salida para prevenir ataques XSS.
- **Consultas Seguras**: Uso de `$wpdb->prepare()` para todas las interacciones con la base de datos.
- **Control de Capacidades**: Restricción de acceso a funcionalidades basado en roles de usuario.

## Tecnologías y Dependencias

- **Core**: WordPress `6.8.2+`, PHP `8.3+`
- **Plugins Requeridos**: Elementor Pro, Advanced Custom Fields (ACF) Pro, CBX PhpSpreadsheet
- **Servicios Externos**:
  - **Email**: Brevo para el envío de correos transaccionales.
- **Frontend**:
  - **JavaScript**: jQuery (incluido en WordPress) para interacciones AJAX.
  - **Estilos**: CSS personalizado para los componentes de la interfaz.
- **Backend**:
  - **Inyección de Dependencias**: `PHP-DI` para gestionar las instancias y el acoplamiento.
- **Desarrollo (Opcional)**:
  - `Node.js` + `MJML` para compilar las plantillas de correo (`.mjml` a `.php`).

## Arquitectura

El plugin sigue el patrón **Clean Architecture**, separando las responsabilidades en capas bien definidas para mejorar la mantenibilidad y escalabilidad.

```
/wp_ulsa_riilsa
├── includes/
│   ├── Domain/         # Reglas de negocio y entidades puras (agnósticas a WordPress).
│   ├── Application/    # Casos de uso que orquestan las reglas de negocio.
│   ├── Infrastructure/ # Implementaciones concretas (WordPress, Brevo, Base de Datos).
│   ├── Presentation/   # Controladores, Endpoints AJAX y hooks de WordPress.
│   └── Core/           # Bootstrap, contenedor de dependencias, constantes y helpers.
├── assets/             # Archivos públicos (JS, CSS, imágenes).
├── templates/          # Plantillas de correo y vistas.
└── tests/              # Pruebas unitarias y de integración.
```

Para una descripción detallada de cada capa, consulta los archivos `README.md` dentro de cada directorio.

## Instalación

1.  Clona o descarga el repositorio en la carpeta `wp-content/plugins/`.
2.  Navega a la raíz del plugin en tu terminal:
    ```bash
    cd wp-content/plugins/wp_ulsa_riilsa
    ```
3.  Instala las dependencias de Composer:
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
4.  Crea un archivo `.env` en la raíz del plugin y añade tu clave de API de Brevo:
    ```
    BREVO_API_KEY=tu_api_key_aqui
    ```
5.  Activa el plugin desde el panel de administración de WordPress.

## Historial de Versiones

- **4.0.0**: Refactorización completa a una arquitectura limpia. Estandarización de módulos, inyección de dependencias, mejoras de seguridad y experiencia de desarrollador (DX).
- **3.0.0**: Estabilización general, mejoras en el sistema de envío de boletines y gestión de errores.
- **2.0.0**: Implementación del módulo de Newsletter (selección de contenido, generación de HTML, envío, suscriptores).
- **1.0.0**: Versión inicial con el gestor de contenido desde Excel (procesamiento de archivos, creación de posts, taxonomías).

## Licencia

Este plugin es de **código propietario** y su uso está restringido a la organización **RIILSA**.

---

<p align="center">
  <strong>&copy; 2025</strong> &bull; Desarrollado por <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
