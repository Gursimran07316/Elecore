# Elecora — Drupal 11 Electronics Site with AI Laptop Assistant

A Drupal 11 electronics storefront/landing page using a custom theme (`elecora_theme`), Paragraphs-based sections, and a custom module (`Elecore mod`) that supplies a **Views style twig + preprocess** for rendering product cards. The site also includes an **AI agent (Drupal AI 1.1)** to answer laptop product questions (work-in-progress).

> This README documents the exact files and behavior you shared. If you want me to add the remaining YAML (e.g., `*.libraries.yml`) or the AI agent’s final model/provider settings, send those and I’ll append them.

---

## Tech Stack

- **Drupal:** 11 (compatible 10/11)
- **PHP:** 8.3 (Lando appserver)
- **DB:** MySQL 8.0 (Lando)
- **Key contrib:** Paragraphs, Views, Drupal AI 1.1
- **Custom theme:** `elecora_theme`
- **Custom module:** `Elecore mod`

---

## Repository Layout (Relevant Parts)

```
web/
  themes/
    custom/
      elecora_theme/
        templates/
          page.html.twig
          block.html.twig
          block--system-menu-block.html.twig
          container.html.twig
          paragraph--services-block--cta-paragraph.html.twig
          paragraph--cta-paragraph.html.twig
        elecora_theme.theme
        elecora_theme.info.yml
  modules/
    custom/
      elecore_mod/
        elecore_mod.module
        templates/
          views-style-elecore-mod-products-style.html.twig
```

---

## Custom Theme — `elecora_theme`

### `elecora_theme.info.yml`
```yaml
name: 'elecora theme'
type: theme
base theme: stark
description: A flexible theme with a responsive, mobile-first layout.
package: Custom
core_version_requirement: ^10 || ^11
libraries:
  - elecora_theme/global
regions:
  header: 'Header'
  hero_section: 'Hero Section'
  collection_section: 'Collection Section'
  products_section: 'Products Section'
  content: 'content'
  cta_section: 'CTA Section'
  services: 'services'
  insta_section: 'Insta Section'
  footer: 'Footer'
```

**Notes**
- Declares a single global library: `elecora_theme/global` (ensure this is defined in `elecora_theme.libraries.yml`).
- Defines custom regions matching the landing page structure (hero, collection, products, CTA, services, etc.).
- Uses **Stark** as base theme (developer-friendly minimal base).

### `elecora_theme.theme` (key logic)

```php
<?php
declare(strict_types=1);

/**
 * @file
 * Functions to support theming in the elecora theme theme.
 */

function elecora_theme_preprocess_html(array &$variables): void {}
function elecora_theme_preprocess_page(array &$variables): void {}
function elecora_theme_preprocess_node(array &$variables): void {}

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Implements hook_theme_suggestions_paragraph_alter().
 */
function elecora_theme_theme_suggestions_paragraph_alter(array &$suggestions, array $variables) {
  if (
    isset($variables['elements']['#paragraph']) &&
    $variables['elements']['#paragraph'] instanceof Paragraph
  ) {
    $paragraph = $variables['elements']['#paragraph'];
    $bundle = $paragraph->bundle();
    $view_mode = $paragraph->view_mode;

    // Add suggestion based on view mode: paragraph--{bundle}--{view_mode}.html.twig
    if (!empty($view_mode)) {
      $suggestions[] = 'paragraph__' . $bundle . '__' . $view_mode;
    }

    // Add suggestion based on parent entity bundle.
    if ($paragraph->getParentEntity()) {
      $parent = $paragraph->getParentEntity();
      $parent_type = $parent->getEntityTypeId(); // e.g., 'block_content', 'node', etc.
      $parent_bundle = $parent->bundle();        // e.g., 'services_block'

      \Drupal::logger('theme_suggestion')->notice('Paragraph inside: @type / @bundle', [
        '@type' => $parent_type,
        '@bundle' => $parent_bundle,
      ]);

      // Suggestion like: paragraph--{parent_bundle}--{bundle}.html.twig
      $suggestions[] = 'paragraph__' . $parent_bundle . '__' . $bundle;
    }
  }
}
```
**What this does**
- Enables highly specific Twig overrides for Paragraphs:
  - By **bundle + view mode** (e.g., `paragraph--cta-paragraph--full.html.twig`).
  - By **parent bundle + current bundle** (e.g., `paragraph--services-block--cta-paragraph.html.twig`).  
    This matches templates you have in `templates/` and allows tight control over nested layout cases.

### Theme Templates
- `page.html.twig` — Page wrapper (header, main, footer, region output).
- `container.html.twig` — A reusable container wrapper for layout spacing and max-width.
- `block.html.twig` — Base block markup.
- `block--system-menu-block.html.twig` — Customized primary menu rendering.
- Paragraph templates:
  - `paragraph--cta-paragraph.html.twig`
  - `paragraph--services-block--cta-paragraph.html.twig`

> These Paragraph templates expect fields like titles, descriptions, media, and links. If you share the field machine names for each paragraph type, I’ll document them explicitly.

---

## Custom Module — `Elecore mod`

### `elecore_mod.info.yml`
```yaml
name: 'Elecore mod'
type: module
description: '@todo Add description.'
package: '@todo Add package'
core_version_requirement: ^10 || ^11
```

> You can replace the placeholders with something like:
> - **description:** “Custom Views style markup and preprocessors for product listings.”
> - **package:** “Elecora”

### `elecore_mod.module` (Views style preprocess)

```php
<?php

declare(strict_types=1);

/**
 * @file
 * Primary module hooks for Elecore mod module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for views-style-elecore-mod-products-style.html.twig template.
 */
function template_preprocess_views_style_elecore_mod_products_style(array &$variables): void {
  $view = $variables['view'];
  $options = $view->style_plugin->options;

  // Wrapper classes from style plugin options.
  if ($options['wrapper_class']) {
    $variables['attributes']['class'] = explode(' ', $options['wrapper_class']);
  }

  $variables['default_row_class'] = $options['default_row_class'];

  foreach ($variables['rows'] as $id => $row) {
    $variables['rows'][$id] = [
      'content' => $row,
      'attributes' => new Attribute(),
    ];

    // Product fields extracted from the row's entity:
    $entity = $row['#row']->_entity;
    $variables['rows'][$id]['title'] = $entity->getTitle();
    $variables['rows'][$id]['price'] = $entity->get('field_product_price')->getValue()[0]['value'] ?? NULL;

    // Optional product image URL.
    if (!$entity->get('field_product_image')->isEmpty()) {
      $file = $entity->get('field_product_image')->entity;
      if ($file) {
        $variables['rows'][$id]['image'] = \Drupal::service('file_url_generator')->generateString($file->getFileUri());
      }
    }

    // Row classes if provided by the style plugin.
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $variables['rows'][$id]['attributes']->addClass($row_class);
    }
  }
}
```

### `templates/views-style-elecore-mod-products-style.html.twig`
A dedicated **Views style** template that renders product cards using the preprocessed variables above (`title`, `price`, `image`, `attributes`, etc.).

**Important note about usage:**  
This template + preprocess implies a **Views style plugin** with theme hook `views_style_elecore_mod_products_style`. If you don’t see “Elecore Mod Products Style” (or similar) in the **Views → Format (Show/Format)** UI:
- Ensure the module that **declares the style plugin** is present (a `Plugin/views/style/*.php` class that sets `theme = "views_style_elecore_mod_products_style"`), or
- You can temporarily render via a custom block/theme template, but the intended use is a proper Views Style plugin so the `$view->style_plugin` options (`wrapper_class`, `default_row_class`) are available.

> If you have the plugin class, send it and I’ll document the exact steps/UI label.

### Expected Product Fields
The preprocess accesses:
- **Node title** → `$entity->getTitle()`
- **Price** → `field_product_price` (numeric field; first value used)
- **Image** → `field_product_image` (image field; first file used)

Make sure your **Product** content type defines at least:
- `field_product_price` (Decimal/Float/Integer)
- `field_product_image` (Image)

---

## AI Laptop Assistant (Drupal AI 1.1)

- **Purpose:** Chatbot that answers laptop-related questions using your product data.
- **Status:** Under development.
- **Setup (typical):**
  1. Enable **Drupal AI** and the provider submodule (e.g., OpenAI).
  2. Add provider credentials (UI or `settings.local.php` env vars).
  3. Create an **AI Agent**:
     - Model (e.g., GPT-4o / 4.1-mini, or another provider).
     - System prompt: scope to *only* your catalog; encourage citing product names/prices.
     - Retrieval: point to Product data (Search API / entity queries / View JSON).  
  4. Place the **chat block** on relevant pages or expose a route.
- **Security:** Store API keys outside Git; use environment variables.

> Share your exact provider/model and retrieval approach and I’ll lock these steps to your implementation.

---

## Local Setup (Lando)

```bash
# Clone & install
git clone <repo-url> elecora
cd elecora
composer install
lando start

# Install Drupal (example credentials from your notes)
lando ssh
drush site:install standard --db-url='mysql://drupal11:drupal11@database/drupal11' -y

# If public files directory is missing:
mkdir -p web/sites/default/files
chmod -R 775 web/sites/default/files

# Enable theme & modules
drush theme:enable elecora_theme
drush config-set system.theme default elecora_theme -y
drush en paragraphs -y
drush en elecore_mod -y
# drush en drupal_ai -y   # enable the AI module you’re using
drush cr
```

---

## Building the Landing Page

1. **Paragraph Types**  
   Create/confirm the Paragraph types that your templates target (CTA paragraph, Services+CTA, Collection, Client section). Map their fields to the markup in the Twig files.
2. **Blocks & Menus**  
   Assign a menu to the primary region; `block--system-menu-block.html.twig` styles it.
3. **Product Listing (Views)**  
   - Create a View of **Content → Product**.  
   - **Format:** Select your custom style (if the Views Style plugin is present).  
   - Add fields or use “Content” row style depending on your plugin/template design.  
   - Ensure the view exposes the node entity in `$row['#row']->_entity` (Row style: “Content” often does this).

> If you prefer, I can export a `config/sync` for these pieces once you confirm field names on all paragraphs and the Product type.

---

## Troubleshooting

- **Upload directory cannot be created**  
  Ensure `web/sites/default/files` exists and is writable.
- **Custom Paragraph template not applied**  
  Run `drush cr`. Confirm template suggestion matches your Paragraph **bundle** and **view mode**, or the **parent bundle** + current bundle as per `elecora_theme_theme_suggestions_paragraph_alter()`.
- **Views style not visible**  
  You likely need the **Views style plugin class** that references `views_style_elecore_mod_products_style`. Send it over and I’ll document/verify.
- **AI agent not responding**  
  Check provider API key, model name, and outbound access from the container. Log errors via Recent log messages or `\Drupal::logger`.

---

## Roadmap

- Add `elecora_theme.libraries.yml` contents (scripts/styles) and reference points.
- Document Paragraph field machine names and mapping for each template.
- Add the missing Views Style **plugin** (if not already in the repo) and step-by-step setup screenshots.
- Finalize AI Agent config: provider, model, retrieval strategy, and guardrails.
- Export `config/sync` for one-click provisioning.
- Add PHPCS/PHPStan/TwigCS tooling.

---

## Maintainer

**Gursimran (Guri)** — Portfolio: https://gursimrankhela.com/


---

## Architecture Diagram

Below is a simplified diagram of how content types, Paragraphs, and custom code integrate in **Elecora**:

```mermaid
graph TD
    subgraph Drupal_Backend["Drupal Backend"]
        Products[Product Content Type\n- field_description\n- field_product_image\n- field_product_price]
        CTA_Paragraph[CTA Paragraph Type\n- field_cta_title\n- field_cta_description\n- field_cta_image]
        Other_Paragraphs[Other Paragraph Types\n(Collection, Services+CTA, Client Section)]
        Views[Drupal View: Product Listing]
        AI_Agent[Drupal AI 1.1 Agent]
    end

    subgraph Theme["Custom Theme: elecora_theme"]
        Page_Twig[page.html.twig (Regions: header, hero_section, collection_section, etc.)]
        Paragraph_Twig[paragraph--*.html.twig Templates]
        Views_Twig[views-style-elecore-mod-products-style.html.twig]
        CSS_JS[elecora_theme/global\ncss/style.css, js/elecora-theme.js, js/script.js]
    end

    subgraph Module["Custom Module: Elecore mod"]
        Preprocess[template_preprocess_views_style_elecore_mod_products_style()]
    end

    Products --> Views
    Views --> Preprocess
    Preprocess --> Views_Twig

    CTA_Paragraph --> Paragraph_Twig
    Other_Paragraphs --> Paragraph_Twig

    Paragraph_Twig --> Page_Twig
    Views_Twig --> Page_Twig

    AI_Agent --> Page_Twig

    CSS_JS --> Page_Twig
```

**Explanation**:
- **Product nodes** feed into a **View**, which uses the custom Views style preprocess + Twig template from the **Elecore mod** module.
- **Paragraph entities** (CTA, Collection, Services+CTA, Client Section) render through bundle-specific Twig templates in the theme.
- The **page.html.twig** template assembles all regions (header, hero, products, etc.) and outputs the full page.
- **Theme assets** from `elecora_theme/global` provide global styling and JavaScript.
- The **AI Agent** integrates into a block or region to provide Q&A functionality about products.


---

## Architecture Diagram

```
                 ┌────────────────────────────────────────────────┐
                 │                    THEME                       │
                 │                elecora_theme                   │
                 └────────────────────────────────────────────────┘
                                   ▲            ▲
                                   │            │
                 Libraries         │            │  Twig suggestions
     elecora_theme/global          │            │  (elecora_theme.theme)
     ├─ js/elecora-theme.js        │            │   • paragraph--{bundle}--{view_mode}.html.twig
     ├─ js/script.js               │            │   • paragraph--{parent_bundle}--{bundle}.html.twig
     └─ css/style.css              │            │
                                   │            │
         Regions in page.html.twig │            │ Paragraph templates
     ┌──────────────────────────────┴────────────┴──────────────────────────┐
     │ header | hero_section | collection_section | products_section | ...   │
     └───────────────────────────────────────────────────────────────────────┘
                                                        │
                                                        │ Renders fields from Paragraph items
                                                        ▼
          Paragraph Types (examples)  ┌──────────────────────────────────────┐
          cta_paragraph               │ paragraph--cta-paragraph.html.twig   │
          ├─ field_cta_title          └──────────────────────────────────────┘
          ├─ field_cta_description
          └─ field_cta_image

```

```
                    ┌────────────────────────────────────────────────┐
                    │                 PRODUCTS FLOW                  │
                    └────────────────────────────────────────────────┘

 Product content type
 ├─ field_description (text_long)
 ├─ field_product_image (image)
 └─ field_product_price (decimal)
               │
               │ Exposed to Views (Content: Products)
               ▼
         Views (listing)
               │  Format → (custom style plugin/theme hook)
               │  theme: views_style_elecore_mod_products_style
               ▼
   template_preprocess_views_style_elecore_mod_products_style()
   (elecore_mod.module)
   ├─ Builds row variables:
   │    title, price, image, attributes, default_row_class
   └─ Reads style options: wrapper_class, row classes
               ▼
 templates/views-style-elecore-mod-products-style.html.twig
               ▼
       Product cards on the frontend
```

```
                   ┌────────────────────────────────────────────────┐
                   │                 AI ASSISTANT                    │
                   │                (Drupal AI 1.1)                  │
                   └────────────────────────────────────────────────┘

User question ─► AI Agent (provider/model) ─► Retrieval (Products via View/Search API/entity query)
                                 │
                                 └─ System prompt constrains scope to laptops
                                     and requests citing product name/price.
Output: Helpful answer grounded in product fields (title, price, image/links).
```
