<div class="w-full">
    <div style="width: 100%; max-width: 100%;" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 w-full">
        <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
            <h3 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">
                Project Documentation
            </h3>
        </div>
        
        <div class="fi-section-content p-6 pt-0">
            <div class="prose prose-sm dark:prose-invert max-w-none markdown-content w-full">
                {!! $this->getContent() !!}
            </div>
        </div>
        
        <div class="fi-section-footer flex items-center justify-between gap-6 px-6 py-4">
            <div class="text-xs text-gray-500">
                Content from /README.md
            </div>
        </div>
    </div>

    <style>
        /* Fix for full-width display */
        .fi-wi-markdown-widget {
            width: 100% !important;
            max-width: 100% !important;
            grid-column: 1 / -1 !important;
        }
        
        /* GitHub-like markdown styling */
        .markdown-content {
            line-height: 1.6;
            width: 100%;
            display: block;
        }
        
        .markdown-content h1, 
        .markdown-content h2 {
            border-bottom: 1px solid #eaecef;
            padding-bottom: 0.3em;
        }
        
        .markdown-content code {
            background-color: rgba(175, 184, 193, 0.2);
            border-radius: 6px;
            padding: 0.2em 0.4em;
            font-family: ui-monospace, SFMono-Regular, SF Mono, Menlo, Consolas, Liberation Mono, monospace;
        }
        
        .markdown-content pre {
            background-color: #f6f8fa;
            border-radius: 6px;
            padding: 16px;
            overflow: auto;
        }
        
        .markdown-content pre code {
            background-color: transparent;
            padding: 0;
        }
        
        .markdown-content blockquote {
            border-left: 0.25em solid #d0d7de;
            color: #656d76;
            padding: 0 1em;
        }
        
        .markdown-content table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 16px;
        }
        
        .markdown-content table th,
        .markdown-content table td {
            border: 1px solid #d0d7de;
            padding: 6px 13px;
        }
        
        .markdown-content table tr {
            background-color: #ffffff;
            border-top: 1px solid #d0d7de;
        }
        
        .markdown-content table tr:nth-child(2n) {
            background-color: #f6f8fa;
        }
        
        .markdown-content img {
            max-width: 100%;
            box-sizing: content-box;
        }
        
        .markdown-content .task-list-item {
            list-style-type: none;
        }
        
        /* Dark mode adjustments */
        .dark .markdown-content pre {
            background-color: #161b22;
        }
        
        .dark .markdown-content code {
            background-color: rgba(110, 118, 129, 0.4);
        }
        
        .dark .markdown-content table tr {
            background-color: #0d1117;
            border-top: 1px solid #30363d;
        }
        
        .dark .markdown-content table tr:nth-child(2n) {
            background-color: #161b22;
        }
        
        .dark .markdown-content table th,
        .dark .markdown-content table td {
            border: 1px solid #30363d;
        }
        
        .dark .markdown-content blockquote {
            border-left: 0.25em solid #30363d;
            color: #8b949e;
        }
    </style>
</div>