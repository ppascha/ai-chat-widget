# ARCHITECTURE.md

## Architecture for ai-chat Plugin V1

This deployment view shows the production shape we are targeting for a WooCommerce-friendly WordPress install: the customer site stays in one WordPress server, the chat widget talks only to the ai-loop, and the MCP app stays behind that loop as a separate capability boundary. The LLM provider remains external.

```mermaid
flowchart TB
    %% End-user browser that loads the WordPress site and chat widget.
    browser["`**End-user browser**
    - loads the WordPress page
    - sends chat messages
    - renders chat text and embedded iframe cards`"]

    %% Customer-owned WordPress deployment after installation.
    subgraph wp_host["`**Customer WordPress server**
    - end-user deployment target`"]
        wp_core["`**WordPress core + theme + content**
        - site pages
        - wp-admin
        - wp-json runtime
        - WooCommerce storefront (optional)`"]

        ai_chat_plugin["`**ai-chat-widget plugin**
        - floating chat UI shell
        - chat REST endpoint
        - iframe/resource routes
        - product intent routing`"]

        ai_loop["`**AI loop component**
        - request-time orchestration
        - routes greetings vs product requests
        - calls WordPress tools/resources`"]

        mcp_app["`**MCP app component**
        - declares tools and resources
        - maps products to ui:// resources
        - serves iframe-renderable HTML payloads`"]
    end

    %% External service not shipped with the customer WordPress install.
    llm_service["`LLM provider service
    - OpenAI / Anthropic / other model API`"]

    %% Styling: keep the same border language used below.
    classDef backend stroke:#800080,stroke-width:2px;
    classDef wpServer stroke:#008000,stroke-width:2px;
    classDef thirdParty stroke:#0000FF,stroke-width:2px,stroke-dasharray: 5 5;

    class ai_loop,mcp_app backend;
    class wp_core,ai_chat_plugin wpServer;
    class browser,llm_service thirdParty;

    %% Service connections.
    browser <-->|HTTPS| ai_chat_plugin
    ai_chat_plugin <-->|same WordPress server| wp_core
    ai_chat_plugin <-->|in-process request handling| ai_loop
    ai_loop <-->|tool/resource calls| mcp_app
    ai_loop <-->|HTTPS| llm_service

    mcp_app -->|call wp APIs| wp_core
    
    %% mcp_app <-->|ui:// resource HTML| ai_chat_plugin

```

## Architecture for ai-chat Plugin - Phase 2

This diagram shows what lives on the customer's WordPress server after deployment, what we write for the spike, and what remains external. The chat widget only communicates with the ai-loop; the ai-loop then decides whether to call tools/resources or return plain text.

If we later split the MCP app onto its own server, the current in-process MCP app should already mirror the same tool/resource boundary and serialization shape. That makes the `modelcontextprotocol/php-sdk` a likely extraction target, but it does not need to be a hard dependency yet while the MCP app remains inside WordPress.

```mermaid
flowchart TB
    %% End-user browser that loads the WordPress site and chat widget.
    browser["`**End-user browser**
    - loads the WordPress page
    - sends chat messages
    - renders plain text replies and iframes`"]

    %% Customer-owned WordPress server after deployment.
    subgraph wp_host["`**Customer WordPress server**
    - this is the end-user deployment target`"]
        wp_core["`**WordPress core + theme + content**
        - site pages
        - wp-admin
        - wp-json runtime`"]

        ai_chat_plugin["`**ai-chat-widget plugin**
        - widget shell and JS
        - chat REST endpoint
        - product catalog routes
        - iframe/resource routes`"]
    end

    %% Services written by us for the spike.
    subgraph our_services["`**Written by us now**
    - spike-time orchestration and adapters`"]
        ai_loop["`**AI loop / chat loop**
        - intent routing
        - asks the plugin for data
        - asks the LLM for text or iframe intent`"]
    end

    mcp_app["`**MCP app facade**
    - declares tools and resources
    - maps product categories to iframe URIs
    - talks to WordPress APIs if needed`"]

    %% External service not written by us.
    llm_service["`LLM provider service
    - OpenAI / Anthropic / other model API`"]

    %% Styling: keep the existing border language, but add a distinct WordPress host color.
    classDef backend stroke:#800080,stroke-width:2px;
    classDef wpServer stroke:#008000,stroke-width:2px;
    classDef thirdParty stroke:#0000FF,stroke-width:2px,stroke-dasharray: 5 5;

    class ai_loop,mcp_app backend;
    class wp_core,ai_chat_plugin wpServer;
    class browser,llm_service thirdParty;

    %% Service connections.
    browser <-->|HTTPS| ai_chat_plugin
    ai_chat_plugin <-->|HTTPS| ai_loop
    ai_loop <-->|HTTPS + JRPC 2.0| mcp_app
    ai_loop <-->|HTTPS| llm_service
    mcp_app <-->|HTTPS| wp_core
    ai_chat_plugin <-->|same server| wp_core

```

## Appendix

Practical note: the current WordPress-hosted MCP app can stay in-process for the spike. If the MCP app becomes a dedicated server later, adopt the PHP MCP SDK at that boundary so the tool/resource contract stays stable and only the transport moves.

The Storefront Integration boundary is the local extraction seam: Demo WP and WooCommerce provide their own catalogs, MCP capability definitions, and tool executors, while the chat loop, REST contracts, and MCP app facade remain shared. The active Storefront Integration is selected by the deployment environment, so the WooTestSite1 fixture can be provisioned independently of the AI Chat plugin.


This is a tried architecture deployed for different past project, for an ai-chat interface with ability to render iframes inline the chat.

```mermaid
flowchart TB
    %% Frontend - App with ai-chat UI
    app["`App with Chat`"]

    %% 3rd-party - Website with content - iframe has content from here
    website["`**Website with content**
    \- iframe shows content from here `"]

    %% Backend - AI (loop) service
    ai_loop["`**AI Bot**
    \- loop service
    \- asks mcp app for tools
    \- asks llm for response based on User msg and Tools
    \- handles llm response with 'iframe', using ui:// Resources`"]

    %% Backend - MCP App server
    mcp_app["`**MCP App server**
    \- registry of Tools
    \- calls the executables
    \- registry of ui:// Resources
    `"]

    %% 3rd-party LLM Provider service (openAI API server: chat completions / chat request, etc)
    llm_service["`LLM Provider service
    \- serves /chat/completions
    \- serves /chat/request`"]

    %% Protocols: HTTPS
    %% Data Serialization: JRPC 2.0 and JSON

    %% backend services have purple color for box border ONLY
    classDef backend stroke:#800080,stroke-width:2px;

    %% thirdParty services have different styling of border ONLY
    classDef thirdParty stroke:#0000FF,stroke-width:2px,stroke-dasharray: 5 5;

    class app,ai_loop,mcp_app backend;
    class website,llm_service thirdParty;


    %% service connections
    app <-->|HTTPS| ai_loop;
    ai_loop <-->|HTTPS + JRPC 2.0| mcp_app
    ai_loop <-->|HTTPS| llm_service

    website .-> app

```
