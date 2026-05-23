<!-- resources/views/docs.blade.php -->
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>API Specifications Blueprint</title>
    
    <!-- Fonts & Styling -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
    
    <style>
        :root {
            --brand-primary: #3b82f6;
            --brand-secondary: #8b5cf6;
            --bg-dark: #090d16;
            --panel-glass: rgba(17, 24, 39, 0.75);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-dark);
            color: var(--text-main);
        }

        /* Stoplight Element Customization overrides */
        .sl-elements {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }

        /* Top Header Navigation Panel */
        .premium-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 32px;
            background: rgba(10, 15, 28, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-glass);
            height: 60px;
            box-sizing: border-box;
            z-index: 100;
            position: relative;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-badge {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            box-shadow: 0 0 16px rgba(59, 130, 246, 0.4);
        }

        .logo-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
        }

        .control-area {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        /* Premium Buttons */
        .btn-premium {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-main);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Glassmorphism Client Code Drawer */
        .drawer-overlay {
            position: fixed;
            top: 0;
            right: -600px;
            width: 550px;
            height: 100vh;
            background: var(--panel-glass);
            backdrop-filter: blur(20px);
            border-left: 1px solid var(--border-glass);
            z-index: 9999;
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
        }

        .drawer-overlay.active {
            right: 0;
        }

        .drawer-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .drawer-title {
            font-size: 18px;
            font-weight: 700;
            background: linear-gradient(135deg, #60a5fa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close-btn:hover {
            color: #ffffff;
        }

        .drawer-content {
            padding: 24px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Language Tab Bar */
        .tab-bar {
            display: flex;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 4px;
            gap: 4px;
        }

        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .code-container {
            flex: 1;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 16px;
            overflow: auto;
            position: relative;
        }

        .code-display {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #e5e7eb;
            white-space: pre;
            margin: 0;
        }

        .copy-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-muted);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        /* Toast notifications */
        .toast-notify {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .toast-notify.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Embed wrapper */
        .elements-wrapper {
            height: calc(100vh - 60px);
            width: 100%;
        }

        /* Premium Light Theme Overrides */
        body.theme-light {
            background: #f9fafb;
            color: #1f2937;
        }
        body.theme-light .premium-header {
            background: rgba(255, 255, 255, 0.85);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        body.theme-light .logo-title {
            color: #111827;
        }
        body.theme-light .btn-secondary {
            background: rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.08);
            color: #374151;
        }
        body.theme-light .btn-secondary:hover {
            background: rgba(0, 0, 0, 0.08);
            color: #111827;
        }
        body.theme-light .drawer-overlay {
            background: rgba(255, 255, 255, 0.85);
            border-left: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
        }
        body.theme-light .close-btn {
            color: #6b7280;
        }
        body.theme-light .close-btn:hover {
            color: #111827;
        }
        body.theme-light .tab-bar {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        body.theme-light .tab-btn {
            color: #4b5563;
        }
        body.theme-light .tab-btn.active {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        body.theme-light .code-container {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        body.theme-light .code-display {
            color: #1f2937;
        }
        body.theme-light .copy-btn {
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.08);
            color: #4b5563;
        }
        body.theme-light .copy-btn:hover {
            background: rgba(0, 0, 0, 0.08);
            color: #111827;
        }
    </style>
</head>
<body>

    <!-- Premium Header -->
    <header class="premium-header">
        <div class="logo-area">
            <img src="https://img.shields.io/badge/Laravel%20API%20Blueprint-v1.0.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel API Blueprint" height="32" style="border-radius: 4px;">
            <div class="logo-title">Specifications Dashboard</div>
        </div>
        <div class="control-area">
            <button class="btn-premium" onclick="openDrawer()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/></svg>
                Client Schemas
            </button>
            <button class="btn-secondary" onclick="toggleTheme()">Toggle Dark/Light</button>
        </div>
    </header>

    <!-- Stoplight Doc Engine -->
    <div class="elements-wrapper">
        <elements-api 
            id="docs-engine"
            apiDescriptionUrl="{{ $schemaUrl }}" 
            router="hash" 
            layout="sidebar"
            appearance="dark">
        </elements-api>
    </div>

    <!-- Slide-out Drawer Panel for Multi-tech Code generation -->
    <div class="drawer-overlay" id="code-drawer">
        <div class="drawer-header">
            <div class="drawer-title">Dynamic Code Generators</div>
            <button class="close-btn" onclick="closeDrawer()">&times;</button>
        </div>
        <div class="drawer-content">
            <div class="tab-bar">
                <button class="tab-btn active" onclick="switchLanguage('ts')">TypeScript</button>
                <button class="tab-btn" onclick="switchLanguage('swift')">Swift</button>
                <button class="tab-btn" onclick="switchLanguage('java')">Java</button>
                <button class="tab-btn" onclick="switchLanguage('dart')">Dart</button>
                <button class="tab-btn" onclick="switchLanguage('go')">Go</button>
            </div>
            
            <div style="font-size:12px; color: var(--text-muted);">
                Select target schemas generated directly from active endpoint payload validation requirements.
            </div>

            <div class="code-container">
                <button class="copy-btn" onclick="copyCode()">Copy Code</button>
                <pre><code class="code-display" id="code-box">Loading client schemas...</code></pre>
            </div>
        </div>
    </div>

    <!-- Active Toast -->
    <div class="toast-notify" id="toast">Code copied successfully!</div>

    <script>
        let currentLang = 'ts';
        let apiSchema = null;

        function toggleTheme() {
            const body = document.body;
            const isLight = body.classList.toggle('theme-light');
            const next = isLight ? 'light' : 'dark';
            localStorage.setItem('blueprint_theme', next);
            
            // Re-create elements-api component to force Stoplight Elements to re-render in the new theme
            const container = document.querySelector('.elements-wrapper');
            const newEl = document.createElement('elements-api');
            newEl.id = 'docs-engine';
            newEl.setAttribute('apiDescriptionUrl', "{{ $schemaUrl }}");
            newEl.setAttribute('router', 'hash');
            newEl.setAttribute('layout', 'sidebar');
            newEl.setAttribute('appearance', next);
            
            container.innerHTML = '';
            container.appendChild(newEl);
        }

        // Restore saved theme on initial page boot
        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('blueprint_theme') || 'dark';
            if (savedTheme === 'light') {
                document.body.classList.add('theme-light');
                const el = document.getElementById('docs-engine');
                if (el) {
                    el.setAttribute('appearance', 'light');
                }
            }
        });

        function openDrawer() {
            document.getElementById('code-drawer').classList.add('active');
            fetchSchemaData();
        }

        function closeDrawer() {
            document.getElementById('code-drawer').classList.remove('active');
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.innerText = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }

        async function fetchSchemaData() {
            if (apiSchema) return;
            try {
                const response = await fetch("{{ $schemaUrl }}");
                apiSchema = await response.json();
                renderSchemas();
            } catch (e) {
                document.getElementById('code-box').innerText = "Failed to load validation schemas.";
            }
        }

        function switchLanguage(lang) {
            currentLang = lang;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.innerText.toLowerCase() === lang) {
                    btn.classList.add('active');
                }
            });
            renderSchemas();
        }

        function copyCode() {
            const code = document.getElementById('code-box').innerText;
            navigator.clipboard.writeText(code);
            showToast("Copied to clipboard!");
        }

        function renderSchemas() {
            if (!apiSchema || !apiSchema.paths) return;
            
            let output = "";
            if (currentLang === 'ts') {
                output = generateTS();
            } else if (currentLang === 'swift') {
                output = generateSwift();
            } else if (currentLang === 'java') {
                output = generateJava();
            } else if (currentLang === 'dart') {
                output = generateDart();
            } else if (currentLang === 'go') {
                output = generateGo();
            }
            
            document.getElementById('code-box').innerText = output || "// No validation rules resolved for payload requests.";
        }

        function cleanClassName(uri, summary) {
            let name = summary || uri;
            name = name.replace(/\{[a-zA-Z0-9_]+\}/g, '');
            name = name.replace(/[^A-Za-z0-9]/g, ' ');
            let words = name.toLowerCase().split(' ');
            return words.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('') + 'Request';
        }

        // --- TypeScript Client Generator ---
        function generateTS() {
            let code = "/** TypeScript Interfaces **/\n\n";
            for (let path in apiSchema.paths) {
                for (let method in apiSchema.paths[path]) {
                    let endpoint = apiSchema.paths[path][method];
                    if (endpoint.requestBody) {
                        let className = cleanClassName(path, endpoint.summary);
                        code += `export interface ${className} {\n`;
                        let props = endpoint.requestBody.content['application/json'].schema.properties || {};
                        let reqs = endpoint.requestBody.content['application/json'].schema.required || [];
                        
                        for (let prop in props) {
                            let type = props[prop].type;
                            let tsType = type === 'number' ? 'number' : (type === 'boolean' ? 'boolean' : 'string');
                            let opt = reqs.includes(prop) ? '' : '?';
                            code += `  ${prop}${opt}: ${tsType};\n`;
                        }
                        code += `}\n\n`;
                    }
                }
            }
            return code;
        }

        // --- Swift Codable Struct Generator ---
        function generateSwift() {
            let code = "/** Swift Codable Structures **/\n\nimport Foundation\n\n";
            for (let path in apiSchema.paths) {
                for (let method in apiSchema.paths[path]) {
                    let endpoint = apiSchema.paths[path][method];
                    if (endpoint.requestBody) {
                        let className = cleanClassName(path, endpoint.summary);
                        code += `struct ${className}: Codable {\n`;
                        let props = endpoint.requestBody.content['application/json'].schema.properties || {};
                        let reqs = endpoint.requestBody.content['application/json'].schema.required || [];
                        
                        for (let prop in props) {
                            let type = props[prop].type;
                            let swiftType = type === 'number' ? 'Double' : (type === 'boolean' ? 'Bool' : 'String');
                            let opt = reqs.includes(prop) ? '' : '?';
                            code += `  let ${prop}: ${swiftType}${opt}\n`;
                        }
                        code += `}\n\n`;
                    }
                }
            }
            return code;
        }

        // --- Java Record Generator ---
        function generateJava() {
            let code = "/** Java Records (Java 14+) **/\n\npackage com.blueprint.dto;\n\nimport com.fasterxml.jackson.annotation.JsonProperty;\n\n";
            for (let path in apiSchema.paths) {
                for (let method in apiSchema.paths[path]) {
                    let endpoint = apiSchema.paths[path][method];
                    if (endpoint.requestBody) {
                        let className = cleanClassName(path, endpoint.summary);
                        code += `public record ${className}(\n`;
                        let props = endpoint.requestBody.content['application/json'].schema.properties || {};
                        let fields = [];
                        
                        for (let prop in props) {
                            let type = props[prop].type;
                            let javaType = type === 'number' ? 'Double' : (type === 'boolean' ? 'Boolean' : 'String');
                            fields.push(`  @JsonProperty("${prop}") ${javaType} ${prop}`);
                        }
                        code += fields.join(",\n") + "\n) {}\n\n";
                    }
                }
            }
            return code;
        }

        // --- Dart flutter DTO Generator ---
        function generateDart() {
            let code = "/** Dart Serialization Classes **/\n\n";
            for (let path in apiSchema.paths) {
                for (let method in apiSchema.paths[path]) {
                    let endpoint = apiSchema.paths[path][method];
                    if (endpoint.requestBody) {
                        let className = cleanClassName(path, endpoint.summary);
                        code += `class ${className} {\n`;
                        let props = endpoint.requestBody.content['application/json'].schema.properties || {};
                        
                        for (let prop in props) {
                            let type = props[prop].type;
                            let dartType = type === 'number' ? 'double' : (type === 'boolean' ? 'bool' : 'String');
                            code += `  final ${dartType}? ${prop};\n`;
                        }
                        
                        code += `\n  ${className}({\n`;
                        for (let prop in props) {
                            code += `    this.${prop},\n`;
                        }
                        code += `  });\n\n`;
                        
                        code += `  factory ${className}.fromJson(Map<String, dynamic> json) => ${className}(\n`;
                        for (let prop in props) {
                            code += `    ${prop}: json['${prop}'],\n`;
                        }
                        code += `  );\n\n`;
                        
                        code += `  Map<String, dynamic> toJson() => {\n`;
                        for (let prop in props) {
                            code += `    '${prop}': ${prop},\n`;
                        }
                        code += `  };\n`;
                        code += `}\n\n`;
                    }
                }
            }
            return code;
        }

        // --- Go Struct Generator ---
        function generateGo() {
            let code = "/** Go JSON Structs **/\n\npackage dto\n\n";
            for (let path in apiSchema.paths) {
                for (let method in apiSchema.paths[path]) {
                    let endpoint = apiSchema.paths[path][method];
                    if (endpoint.requestBody) {
                        let className = cleanClassName(path, endpoint.summary);
                        code += `type ${className} struct {\n`;
                        let props = endpoint.requestBody.content['application/json'].schema.properties || {};
                        let reqs = endpoint.requestBody.content['application/json'].schema.required || [];
                        
                        for (let prop in props) {
                            let type = props[prop].type;
                            let goType = type === 'number' ? 'float64' : (type === 'boolean' ? 'bool' : 'string');
                            let opt = reqs.includes(prop) ? '' : '*';
                            let omit = reqs.includes(prop) ? '' : ',omitempty';
                            let goFieldName = prop.charAt(0).toUpperCase() + prop.slice(1);
                            code += `  ${goFieldName} ${opt}${goType} \`json:"${prop}${omit}"\`\n`;
                        }
                        code += `}\n\n`;
                    }
                }
            }
            return code;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('blueprint_theme') || 'dark';
            document.getElementById('docs-engine').setAttribute('appearance', savedTheme);
        });
    </script>
</body>
</html>
