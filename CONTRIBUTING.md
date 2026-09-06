# Contributing

## Development

### `How-to` add support for a new Webshop/Storefront instance

> Adding a new Storefront Integration

Once the factory and registration mechanism already exist, adding a new Storefront Integration should look like this:


|Thing to touch|Required?|Details|
|---|--:|---|
|New concrete Storefront Integration component|**Yes**|For example, `WooCommerce_Storefront_Integration`. It encapsulates availability detection, catalog access, MCP provisioning, tool mappings, resource mappings, and platform-specific behavior.|
|Storefront Integration registration|**Yes, unless auto-discovery is used**|Register the concrete Storefront Integration with the factory/registry, or let it self-register through a WordPress hook.|
|Storefront Integration-specific tests|**Yes**|Verify detection, product mapping, tool execution, resource URLs, and empty/error cases.|
|Core `Chat_Loop`|**No**|It should only depend on the generic MCP/chat contracts.|
|Core MCP app logic|**No**|It should consume the capability exposed by the active integration.|
|REST controllers|**No**|They should use normalized responses independent of the storefront platform.|
|Storefront Integration factory implementation|**No**|The factory should not need a new `match` branch for every Storefront Integration.|
|Existing Storefront Integrations|**No**|Adding WooCommerce must not require modifying the custom catalog Storefront Integration, for example.|
|Docker/e2e fixture|**Only if needed**|Add a fixture or environment when the new integration needs automated end-to-end coverage.|
|UAT documentation|**Recommended**|Add Storefront Integration-specific setup and acceptance scenarios.|

---

**Core Principle**

A new Storefront Integration should add one concrete component, plus its registration and tests. The generic application core should remain unchanged.

---
If we later use automatic discovery or WordPress hooks for registration, the practical implementation becomes:

```
Add Storefront Integration component
Add Storefront Integration tests
Done
```

---

The catalog and provisioner should remain internal implementation details of that Storefront Integration rather than separate components that the core must know about.

### MCP provisioner pattern

Storefront Integrations use one public provisioning entrypoint:

```php
$mcpApp = $provisioner->provision($catalog);
```

Concrete provisioners compose a capability provider and an MCP app factory. The provisioner coordinates capability preparation and MCP app construction, while each composed collaborator supplies Storefront-specific behavior.
