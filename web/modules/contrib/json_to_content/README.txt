<p><strong>JSON Content Builder</strong> is a user-friendly Drupal module that lets you create custom content types and fields simply by pasting or uploading a JSON structure. Ideal for new and experienced site builders, it removes the need for manual field creation and speeds up prototyping and content modeling.</p>

<h3 id="module-project--features">Features</h3>
<ul>
  <li>Paste or upload a JSON structure to generate a new content type with fields.</li>
  <li>Supports common field types such as:
    <ul>
      <li><code>string</code></li>
      <li><code>text</code></li>
      <li><code>boolean</code></li>
      <li><code>integer</code></li>
      <li><code>entity_reference</code> (e.g., referencing users or taxonomy terms)</li>
    </ul>
  </li>
  <li>Provides simple UIs to create and manage content based on JSON structure.</li>
  <li>No manual field creation or YAML writing required.</li>
  <li>Perfect for prototyping, migrations, or empowering non-developers to define content models.</li>
</ul>

<h3 id="module-project--post-installation">Post-Installation</h3>
<ol>
  <li>Enable the module from the Extend page or using Drush: <code>drush en json_content_builder -y</code>.</li>
  <li>Navigate to the following admin pages:
    <ul>
      <li><strong>Build a content type from JSON:</strong> <code>/admin/config/content/json-content-builder</code></li>
      <li><strong>Create content from JSON data:</strong> <code>/admin/content/json-content-create</code></li>
      <li><strong>Export content to JSON file:</strong> <code>/admin/content/json-export</code></li>
    </ul>
  </li>
  <li>Follow the instructions in each form to generate content types, create nodes, or export content.</li>
</ol>
<p>There is no need to manually manage field storage or bundle configuration — the module handles it for you.</p>

<h3 id="module-project--additional-requirements">Additional Requirements</h3>
<ul>
  <li>No additional modules or libraries are required beyond Drupal core.</li>
  <li>Optional: Make sure referenced entities (like <code>user</code> or <code>taxonomy_term</code>) exist when using <code>entity_reference</code> field types.</li>
</ul>

<h3 id="module-project--recommended-libraries">Recommended modules/libraries</h3>
<ul>
  <li><a href="https://www.drupal.org/project/field_group">Field Group</a> – to organize generated fields visually.</li>
  <li><a href="https://www.drupal.org/project/admin_toolbar">Admin Toolbar</a> – for easier navigation to the config form.</li>
</ul>

<h3 id="module-project--support">Supporting this Module</h3>
<p>If you'd like to support ongoing development, suggest features, or contribute code, visit the <a href="https://www.drupal.org/project/issues/json_content_builder">issue queue</a> or connect on <a href="https://github.com/Gursimran07316/json_content_builder">GitHub</a>.</p>

<h3 id="module-project--community-documentation">Community Documentation</h3>
<ul>
  <li>💬 Support and collaboration in the Drupal.org issue queue</li>
  <li>💻 Contributions and feedback welcome</li>
</ul>
