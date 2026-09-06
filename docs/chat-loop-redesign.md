# Chat Loop Redesign — Current Flow and Alternatives

This document visualizes the current `Chat_Loop` orchestration, highlights the unhappy path
that causes meta/aggregate questions to receive canned, card-dumping answers instead of
natural-language summaries, and proposes three best-practices alternatives.

## Current flow (with unhappy path highlighted)

```mermaid
flowchart TD
    A[User message] --> B[Load conversation history]
    B --> C[Seed system prompt if new]
    C --> D[Append user turn]
    D --> E{iteration == 0 AND<br/>looksLikeProductIntent regex match?}
    E -->|yes| F[tool_choice = required]
    E -->|no| G[tool_choice = auto]
    F --> H[Call model]
    G --> H[Call model]
    H --> I{Assistant returned tool_calls?}
    I -->|yes| J[Execute tools, collect $products]
    J --> D
    I -->|no| K{"$products non-empty?"}
    K -->|no| L["kind=text<br/>return model's own text"]
    K -->|yes| M["kind=products<br/>OVERRIDE model text with<br/>'Here are N matching products.'"]
    M --> N[Return canned text + full item cards]

    style E fill:#663,stroke:#f90
    style F fill:#633,stroke:#f00
    style M fill:#633,stroke:#f00
    style N fill:#633,stroke:#f00
```

**Unhappy path (highlighted):** "how many product options do you offer?" matches the keyword
regex, forcing a tool call on turn 0. `list_products` returns all 5 items, the loop ends with a
non-empty `$products`, and the model's real answer (which might have said *"We offer Personal
Computers and PlayStation consoles"*) gets discarded and replaced by the canned template plus
all 5 cards, regardless of what was actually asked.

## Alternative 1 — Trust the model fully (`auto` everywhere, never override text)

```mermaid
flowchart TD
    A[User message] --> B[Load history + append turn]
    B --> C["tool_choice = auto (always)"]
    C --> D[Call model]
    D --> E{tool_calls present?}
    E -->|yes| F[Execute tools, collect $products]
    F --> C
    E -->|no| G["Return model's own text as-is.<br/>Cards render additively IF $products non-empty,<br/>alongside text, never replacing it"]
```

Simplest possible fix: no forcing, no template override. Relies entirely on tool
descriptions and system prompt quality to get the model to call tools when appropriate.

## Alternative 2 — Cold-start nudge only, tool results carry a presentation hint

```mermaid
flowchart TD
    A[User message] --> B[Load history + append turn]
    B --> C{"Conversation is brand new<br/>(no prior assistant turns)?"}
    C -->|yes| D[tool_choice = required, once]
    C -->|no| E[tool_choice = auto]
    D --> F[Call model]
    E --> F
    F --> G{tool_calls present?}
    G -->|yes| H["Execute tool.<br/>Tool result includes<br/>presentation: 'cards' or 'summary'"]
    H --> B
    G -->|no| I["Return model's own text always.<br/>Render cards only if last tool result<br/>declared presentation='cards'"]
```

Forces a tool call only to avoid an idle cold-start UX, never again mid-conversation.
Rendering decision moves from "is array empty" to an explicit contract on the tool result.

## Alternative 3 — Add an aggregate/meta tool and separate planes strictly

```mermaid
flowchart TD
    A[User message] --> B[Load history + append turn]
    B --> C["tool_choice = auto"]
    C --> D[Call model]
    D --> E{tool_calls present?}
    E -->|yes| F{Which tool?}
    F -->|list_products / find_products| G["Item-level tool:<br/>returns products for CARD rendering"]
    F -->|describe_catalog NEW| H["Aggregate tool:<br/>returns counts/categories,<br/>NO items, NO cards"]
    G --> B
    H --> B
    E -->|no| I["Return model's own text always.<br/>Cards render only from item-level<br/>tool results collected this turn"]
```

Gives the model the right tool for the question instead of overloading `list_products` for
both browsing and analytics. The model naturally picks `describe_catalog` for "how many/what
categories" questions and never fetches full item cards for those.

## Recommendation

Ship Alternative 1 first (near-zero risk, fixes both reported issues today), then adopt
Alternative 3 as the durable follow-up once polished aggregate answers are desired.

**Status: Alternative 1 has been implemented.** See `includes/Services/class-chat-loop.php` and
the synced "AI Loop Flowchart" section in [ARCHITECTURE.md](../ARCHITECTURE.md).

