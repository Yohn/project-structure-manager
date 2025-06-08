# Web Application Structure Template

```
{{PROJECT_NAME}}/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── .htaccess
│   └── index.php
├── src/
│   ├── Controller/
│   │   ├── HomeController.php
│   │   └── BaseController.php
│   ├── Model/
│   ├── View/
│   │   ├── layouts/
│   │   │   └── main.php
│   │   └── pages/
│   │       └── home.php
│   ├── Service/
│   ├── Middleware/
│   └── Config/
│       ├── database.php
│       └── app.php
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── config/
│   ├── .env.example
│   └── routes.php
├── database/
│   ├── migrations/
│   └── seeds/
├── tests/
│   ├── Feature/
│   └── Unit/
├── vendor/
├── composer.json
├── README.md
└── .gitignore
```

Generated for: {{PROJECT_NAME}}
{{if FRAMEWORK}}Framework: {{FRAMEWORK}}{{/if}}
{{if DATABASE}}Database: {{DATABASE}}{{/if}}