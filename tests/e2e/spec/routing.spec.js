const { test, expect } = require('@playwright/test');

test.describe('Nginx Routing and Dashboard UI', () => {
    
    test('Root URL should load the Jobs Dashboard and NOT redirect to docs', async ({ page }) => {
        // Go to the root
        const response = await page.goto('/');
        
        // Assert we are still on the root URL
        expect(page.url()).toBe('http://localhost:8080/');
        
        // Assert it is a 200 OK (and not a 301/302 that redirected)
        // Wait, if it redirected, page.url() would be /docs/ so the previous assertion covers it.
        
        // Assert the UI loaded the "Radar de Vagas" text
        await expect(page.locator('text=Radar de Vagas')).toBeVisible();
    });

    test('Docs URL should load the Swagger UI', async ({ page }) => {
        await page.goto('/docs/');
        expect(page.url()).toBe('http://localhost:8080/docs/');
        await expect(page.locator('#swagger-ui')).toBeVisible();
    });
});
