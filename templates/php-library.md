# PHP Library Structure Template

```
{{PROJECT_NAME}}/
├── src/
│   ├── {{if NAMESPACE}}{{NAMESPACE}}/{{/if}}
│   │   ├── Service/
│   │   ├── Model/
│   │   ├── Exception/
│   │   └── Interface/
│   └── {{MAIN_CLASS}}.php
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── {{MAIN_CLASS}}Test.php
├── bin/
│   └── {{PROJECT_NAME}}
├── docs/
│   ├── api/
│   └── examples/
├── .github/
│   └── workflows/
│       └── ci.yml
├── composer.json
├── phpunit.xml.dist
├── phpstan.neon
├── .gitignore
├── README.md
├── CHANGELOG.md
├── LICENSE
└── .editorconfig
```

Generated for: {{PROJECT_NAME}}
{{if AUTHOR}}Author: {{AUTHOR}}{{/if}}
{{if DESCRIPTION}}Description: {{DESCRIPTION}}{{/if}}