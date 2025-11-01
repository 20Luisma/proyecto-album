# 🧠 Clean Marvel Album — Arquitectura Clean + Microservicio OpenAI

Proyecto de demostración de **arquitectura limpia (Clean Architecture)** y **buenas prácticas en PHP 8.2**, dividido en dos capas principales:

1. **Clean Marvel Album (App principal)** → frontend + backend modular.
2. **OpenAI Service (Microservicio)** → servicio separado, responsable de la generación de historias mediante la API de OpenAI.

---

## 🧱 Principios y objetivos

Este proyecto forma parte de un entorno de aprendizaje orientado a la aplicación real de **Clean Architecture**, **SOLID**, **PSR-4**, y **segregación por capas**:

- **Independencia del Framework**  
  La app y el microservicio no dependen de ningún framework externo.
- **Inversión de dependencias (DIP)**  
  La capa de dominio no conoce detalles de infraestructura.
- **Responsabilidad única (SRP)**  
  Cada clase tiene un propósito claro (Router, Controller, Service, etc.).
- **Comunicación por interfaz / puerto-adaptador**  
  El microservicio usa un adaptador HTTP simple (cURL) para conectar con OpenAI.

---

## 🧩 Estructura del repositorio

```bash
.
├── clean-marvel/                   # App principal (MVC simple)
│   ├── src/                        # Código de dominio, aplicación e infraestructura
│   ├── public/                     # Punto de entrada → http://localhost:8080
│   ├── composer.json
│   └── tests/
│
└── clean-marvel/openai-service/    # Microservicio independiente
    ├── src/                        # Controladores y servicios (PSR-4)
    ├── public/                     # Punto de entrada → http://localhost:8081
    ├── .env                        # Clave API de OpenAI (no se sube al repo)
    └── composer.json
```

---

## ⚙️ Flujo técnico

1. El usuario selecciona héroes en la app principal (8080).  
2. La app envía una petición `POST /comics/generate`.  
3. El backend hace un `curl` al microservicio (8081).  
4. El microservicio llama a `https://api.openai.com/v1/chat/completions`.  
5. OpenAI devuelve la historia → el micro responde con JSON.  
6. La app muestra el cómic generado.

---

## 📄 Requerimientos técnicos y despliegue

Para levantar correctamente los servidores, instalar dependencias y configurar entornos:

👉 **Consulta el archivo [`/docs/requirements.md`](./docs/requirements.md)**  

Ahí se detallan:
- Dependencias mínimas (PHP, Composer, cURL)  
- Configuración de entorno (`.env`)  
- Scripts y tasks de VS Code  
- Ejemplos de uso (`curl`, endpoints)  
- Estructura de capas y patrones aplicados  

---

## 🧰 Buenas prácticas aplicadas

- Estructura modular (Domain / Application / Infrastructure / Presentation)
- PSR-4 en ambas aplicaciones
- `composer dump-autoload` tras mover o crear clases
- Manejo de errores con respuestas JSON controladas
- Separación de responsabilidades entre app principal y microservicio
- Carga manual de `.env` mediante `putenv()` para seguridad

---

## ✍️ Autor

Proyecto desarrollado por **Martín Pallante**, con la colaboración de **Alfred**, asistente de IA.  

[
> “Diseñando tecnología limpia, modular y con propósito.”
