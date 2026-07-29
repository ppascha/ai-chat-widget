-- Base site options for the toy storefront fixture.
UPDATE wp_options SET option_value = 'AI Gadget Store' WHERE option_name = 'blogname';
UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'home';
