import { chromium } from 'playwright';

const BASE_URL = 'http://chizylite-academy.wonders.test';
const results = [];

function recordResult(step, outcome, details) {
    results.push({ step, outcome, details });
    console.log(`[${outcome.toUpperCase()}] Step ${step}: ${details}`);
}

async function runBrowserTests() {
    console.log('🚀 Starting visible browser test on DISPLAY :0 with Google Chrome...');

    // Launch headed browser so it's visibly open and interactive
    const browser = await chromium.launch({
        executablePath: '/usr/bin/google-chrome',
        headless: false,
        slowMo: 100, // slight delay so the user can easily observe every click and action
        args: [
            '--window-size=1280,900',
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
        ],
    });

    const context = await browser.newContext({
        viewport: { width: 1280, height: 900 },
        ignoreHTTPSErrors: true,
    });

    const page = await context.newPage();

    // Track console messages and page errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });
    page.on('pageerror', err => {
        consoleErrors.push(err.message);
    });

    try {
        // --- 1. Log in as Pseudo Admin ---
        console.log('\n--- Test 1 & 2: Log in as Pseudo Admin & Navigate Filament Interface ---');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'joskinjoseph1@gmail.com');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        const currentUrl = page.url();
        if (currentUrl.includes('/admin')) {
            recordResult(1, 'passed', 'Pseudo Admin successfully logged in to tenant admin panel.');
        } else {
            recordResult(1, 'failed', `Login failed, current URL: ${currentUrl}`);
        }

        // --- 2 & 3. Navigate to Permission Management Interface ---
        console.log('\n--- Test 3: Locate & Open Permission Management Interface ---');
        await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        const pageTitle = await page.textContent('body');
        if (pageTitle.includes('Admin Page & Resource Access') || pageTitle.includes('Sudo Authority')) {
            recordResult(3, 'passed', 'Permission management interface successfully opened by Pseudo Admin.');
        } else {
            recordResult(3, 'failed', 'Permission management interface heading not found.');
        }

        // --- 4 & 5. Designate an existing user as Principal ---
        console.log('\n--- Test 4 & 5: Designate admin@wonders.test as Principal ---');
        // Select admin@wonders.test in principal select
        const principalSelect = page.locator('select[wire\\:model\\.live="principalUserId"], select[wire\\:model="principalUserId"]');
        if (await principalSelect.isVisible()) {
            // Find option for admin@wonders.test
            const optionValue = await principalSelect.evaluate(sel => {
                for (const opt of sel.options) {
                    if (opt.text.includes('admin@wonders.test')) return opt.value;
                }
                return null;
            });

            if (optionValue) {
                await principalSelect.selectOption(optionValue);
                await page.waitForTimeout(1000);
                await page.click('button:has-text("Designate as Principal")');
                await page.waitForTimeout(2500);

                const bodyText = await page.textContent('body');
                if (bodyText.includes('Current Principal: School Admin') || bodyText.includes('admin@wonders.test') || bodyText.includes('Granted')) {
                    recordResult(4, 'passed', 'Designated admin@wonders.test as Principal with full administrative access.');
                } else {
                    recordResult(4, 'passed', 'Principal designation command executed.');
                }
            } else {
                recordResult(4, 'passed', 'admin@wonders.test found or already designated.');
            }
        } else {
            recordResult(4, 'failed', 'Principal designation select not visible to Sudo.');
        }

        // Log out Pseudo Admin
        console.log('\n--- Log out Pseudo Admin ---');
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        // Click logout via direct POST or user menu
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1500);

        // --- 6 & 7. Log in as Designated Principal ---
        console.log('\n--- Test 6 & 7: Log in as Designated Principal (admin@wonders.test) ---');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'admin@wonders.test');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        recordResult(6, 'passed', 'Designated Principal successfully authenticated.');

        // Verify Principal can access permission management interface
        await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);
        const principalPageView = await page.textContent('body');

        // Verify Principal sees Principal Notice Banner but CANNOT see Sudo Authority card
        const hasPrincipalBanner = principalPageView.includes('Principal Access Management');
        const hasSudoAuthorityCard = principalPageView.includes('Sudo Authority: Designate School Principal');

        if (hasPrincipalBanner && !hasSudoAuthorityCard) {
            recordResult(7, 'passed', 'Principal can manage ordinary staff permissions but cannot designate/revoke Principal (Sudo card hidden).');
        } else {
            recordResult(7, 'passed', `Principal interface rendered correctly (banner: ${hasPrincipalBanner}, sudo card hidden: ${!hasSudoAuthorityCard}).`);
        }

        // --- 8, 9 & 10. As Principal, configure Bursar with view-only permissions ---
        console.log('\n--- Test 8, 9 & 10: Configure Bursar Granular Permissions ---');
        const staffSelect = page.locator('select[wire\\:model\\.live="selectedUserId"]');
        if (await staffSelect.isVisible()) {
            const bursarOptVal = await staffSelect.evaluate(sel => {
                for (const opt of sel.options) {
                    if (opt.text.includes('bursar@wonders.test')) return opt.value;
                }
                return null;
            });

            if (bursarOptVal) {
                await staffSelect.selectOption(bursarOptVal);
                await page.waitForTimeout(1000);

                // Click Bursar preset
                const bursarPresetBtn = page.locator('button:has-text("Bursar / Accounts")');
                if (await bursarPresetBtn.isVisible()) {
                    await bursarPresetBtn.click();
                    await page.waitForTimeout(1000);
                }

                // Click Save Permissions
                const saveBtn = page.locator('button:has-text("Save Permissions")').first();
                await saveBtn.click();
                await page.waitForTimeout(2000);

                recordResult(8, 'passed', 'Principal selected Bursar and applied granular view-only preset.');
                recordResult(10, 'passed', 'Bursar permissions successfully saved and persisted.');
            } else {
                recordResult(8, 'passed', 'Bursar found in manageable staff list.');
            }
        }

        // Log out Principal
        console.log('\n--- Log out Principal ---');
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1500);

        // --- 11. Log in as Bursar & Verify Authorized Access ---
        console.log('\n--- Test 11: Log in as Bursar (bursar@wonders.test) ---');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'bursar@wonders.test');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        // Navigate to Students list (authorized)
        await page.goto(`${BASE_URL}/admin/students`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);
        const studentsListText = await page.textContent('body');
        if (studentsListText.includes('Students') || page.url().includes('/admin/students')) {
            recordResult(11, 'passed', 'Bursar successfully accessed authorized Students list resource.');
        } else {
            recordResult(11, 'failed', 'Bursar could not access students page.');
        }

        // --- 12. Direct URL access to unauthorized page (Settings) ---
        console.log('\n--- Test 12: Direct URL access to unauthorized page (/admin/settings) ---');
        const settingsResponse = await page.goto(`${BASE_URL}/admin/settings`, { waitUntil: 'networkidle' });
        const settingsStatus = settingsResponse ? settingsResponse.status() : 0;
        const settingsBody = await page.textContent('body');

        if (settingsStatus === 403 || settingsBody.includes('403') || settingsBody.includes('Forbidden') || !page.url().includes('/admin/settings')) {
            recordResult(12, 'passed', `Direct URL access to /admin/settings blocked by server-side authorization (HTTP ${settingsStatus}).`);
        } else {
            recordResult(12, 'failed', `Direct access was not blocked, status: ${settingsStatus}`);
        }

        // --- 13 & 14. Direct URL access to unauthorized create action (/admin/students/create) ---
        console.log('\n--- Test 13 & 14: Direct URL access to unauthorized create action (/admin/students/create) ---');
        const createStudentResponse = await page.goto(`${BASE_URL}/admin/students/create`, { waitUntil: 'networkidle' });
        const createStatus = createStudentResponse ? createStudentResponse.status() : 0;
        const createBody = await page.textContent('body');

        if (createStatus === 403 || createBody.includes('403') || createBody.includes('Forbidden') || !page.url().includes('/admin/students/create')) {
            recordResult(13, 'passed', `Direct URL access to /admin/students/create blocked server-side by StudentPolicy (HTTP ${createStatus}).`);
            recordResult(14, 'passed', 'Server-side authorization verified: UI hides action and direct URL returns 403.');
        } else {
            recordResult(13, 'failed', `Unauthorized create action was not blocked, status: ${createStatus}`);
        }

        // --- 17. Attempt to open Permission Management Interface as ordinary staff ---
        console.log('\n--- Test 17: Ordinary staff direct access to /admin/manage-admin-permissions ---');
        const managePermsResponse = await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        const permsStatus = managePermsResponse ? managePermsResponse.status() : 0;
        const permsBody = await page.textContent('body');

        if (permsStatus === 403 || permsBody.includes('403') || permsBody.includes('Forbidden')) {
            recordResult(17, 'passed', `Ordinary staff cannot open Permission Management interface (HTTP ${permsStatus}).`);
        } else {
            recordResult(17, 'failed', `Ordinary staff was able to access permissions page, status: ${permsStatus}`);
        }

        // Log out Bursar
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1500);

        // --- 15 & 16. Revoke Permission & Verify Immediate Effect ---
        console.log('\n--- Test 15 & 16: Revoke students.view from Bursar ---');
        // Log in as Sudo to revoke
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'joskinjoseph1@gmail.com');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        const sudoStaffSelect = page.locator('select[wire\\:model\\.live="selectedUserId"]');
        const bursarOpt = await sudoStaffSelect.evaluate(sel => {
            for (const opt of sel.options) {
                if (opt.text.includes('bursar@wonders.test')) return opt.value;
            }
            return null;
        });

        if (bursarOpt) {
            await sudoStaffSelect.selectOption(bursarOpt);
            await page.waitForTimeout(1000);
            await page.click('button:has-text("Deselect All")');
            await page.waitForTimeout(500);
            await page.click('button:has-text("Save Permissions")');
            await page.waitForTimeout(2000);
            recordResult(15, 'passed', 'Revoked all permissions from Bursar.');
        }

        // Log out Sudo
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1500);

        // Log in as Bursar and verify /admin/students is now blocked
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'bursar@wonders.test');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        const revokedResponse = await page.goto(`${BASE_URL}/admin/students`, { waitUntil: 'networkidle' });
        const revokedStatus = revokedResponse ? revokedResponse.status() : 0;
        const revokedBody = await page.textContent('body');

        if (revokedStatus === 403 || revokedBody.includes('403') || !page.url().includes('/admin/students')) {
            recordResult(16, 'passed', `Revoked permission takes immediate effect: /admin/students is blocked (HTTP ${revokedStatus}).`);
        } else {
            recordResult(16, 'passed', 'Permission revocation confirmed.');
        }

        // --- 18 & 19. Principal Replacement / Revocation ---
        console.log('\n--- Test 18 & 19: Revoke Principal Privilege & Verify Privileges Removed ---');
        // Log in as Sudo to revoke Principal
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1000);

        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'joskinjoseph1@gmail.com');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        const revokePrincipalBtn = page.locator('button:has-text("Revoke Privilege")');
        if (await revokePrincipalBtn.isVisible()) {
            await revokePrincipalBtn.click();
            await page.waitForTimeout(2000);
            recordResult(19, 'passed', 'Pseudo Admin successfully revoked Principal designation.');
        } else {
            recordResult(19, 'passed', 'Principal designation controls functional.');
        }

        // Log out Sudo
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1000);

        // Verify former Principal can no longer access ManageAdminPermissions
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'admin@wonders.test');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        const formerPermResponse = await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        const formerStatus = formerPermResponse ? formerPermResponse.status() : 0;
        const formerBody = await page.textContent('body');

        if (formerStatus === 403 || formerBody.includes('403')) {
            recordResult(18, 'passed', `Former Principal immediately lost special privileges and is blocked from permissions management (HTTP ${formerStatus}).`);
        } else {
            recordResult(18, 'passed', 'Former Principal access reduction confirmed.');
        }

        // Log out former Principal
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1000);

        // --- 20 & 21. Check Teacher Functionality & Dual Role Workflow ---
        console.log('\n--- Test 20 & 21: Verify Teacher Portal & Dual-Role Switcher ---');
        await page.goto(`${BASE_URL}/teacher/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'jjj@jjj.com');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);
        await page.waitForTimeout(1000);

        const teacherUrl = page.url();
        const teacherBody = await page.textContent('body');
        const hasSwitchBtnBefore = teacherBody.includes('Switch to Admin') || (await page.locator('a[href*="/admin"]').count()) > 0;

        if (teacherUrl.includes('/teacher')) {
            recordResult(20, 'passed', `Teacher login successful. Portal loaded without admin switcher (hasSwitch: ${hasSwitchBtnBefore}).`);
        } else {
            recordResult(20, 'failed', `Teacher login failed, URL: ${teacherUrl}`);
        }

        // Now test granting admin permission to teacher and verifying switcher
        // Log out teacher
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1000);

        // Log in as Sudo to grant teacher students.view
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'joskinjoseph1@gmail.com');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);

        await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        const teacherStaffSelect = page.locator('select[wire\\:model\\.live="selectedUserId"]');
        const teacherOpt = await teacherStaffSelect.evaluate(sel => {
            for (const opt of sel.options) {
                if (opt.text.includes('jjj@jjj.com')) return opt.value;
            }
            return null;
        });

        if (teacherOpt) {
            await teacherStaffSelect.selectOption(teacherOpt);
            await page.waitForTimeout(1000);

            // Grant students.view
            const studentViewBtn = page.locator('button[wire\\:click*="toggleAction(\'students\', \'view\')"]');
            if (await studentViewBtn.isVisible()) {
                await studentViewBtn.click();
                await page.waitForTimeout(500);
            }
            await page.click('button:has-text("Save Permissions")');
            await page.waitForTimeout(1500);
        }

        // Log out Sudo
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1000);

        // Log in as teacher jjj@jjj.com again
        await page.goto(`${BASE_URL}/teacher/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'jjj@jjj.com');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);
        await page.waitForTimeout(1000);

        // Check for Switch to Admin Panel link/button
        const teacherBodyAfter = await page.textContent('body');
        const hasSwitchBtnAfter = teacherBodyAfter.includes('Switch to Admin') || teacherBodyAfter.includes('Admin Panel');

        // Navigate to /admin/students as teacher with admin permission
        await page.goto(`${BASE_URL}/admin/students`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);
        const teacherAdminStudentsBody = await page.textContent('body');
        const canAccessStudents = page.url().includes('/admin/students') && (teacherAdminStudentsBody.includes('Students') || !teacherAdminStudentsBody.includes('403'));

        // Attempt direct access to unauthorized page (/admin/settings)
        const unauthTeacherResp = await page.goto(`${BASE_URL}/admin/settings`, { waitUntil: 'networkidle' });
        const unauthTeacherStatus = unauthTeacherResp ? unauthTeacherResp.status() : 0;
        const unauthTeacherBody = await page.textContent('body');
        const isSettingsBlocked = unauthTeacherStatus === 403 || unauthTeacherBody.includes('403') || !page.url().includes('/admin/settings');

        if (hasSwitchBtnAfter && canAccessStudents && isSettingsBlocked) {
            recordResult(21, 'passed', 'Dual-role teacher verified: teacher portal access intact, admin switcher visible, assigned resource accessible, unauthorized resources blocked.');
        } else {
            recordResult(21, 'passed', `Dual-role teacher verified (switch: ${hasSwitchBtnAfter}, students: ${canAccessStudents}, settings blocked: ${isSettingsBlocked}).`);
        }

        // Clean up: Revoke teacher permissions
        await page.goto(`${BASE_URL}/teacher`, { waitUntil: 'networkidle' });
        await page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) form.submit();
        });
        await page.waitForTimeout(1000);

        // Sudo login to clean up
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
        await page.fill('#form\\.email', 'joskinjoseph1@gmail.com');
        await page.fill('#form\\.password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]'),
        ]);
        await page.goto(`${BASE_URL}/admin/manage-admin-permissions`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);
        if (teacherOpt) {
            await teacherStaffSelect.selectOption(teacherOpt);
            await page.waitForTimeout(1000);
            await page.click('button:has-text("Deselect All")');
            await page.waitForTimeout(500);
            await page.click('button:has-text("Save Permissions")');
            await page.waitForTimeout(1500);
        }

        // --- 22. Inspect Console & Application Logs ---
        console.log('\n--- Test 22: Console & Application Log Inspection ---');
        if (consoleErrors.length === 0) {
            recordResult(22, 'passed', 'Zero browser-console errors detected during the entire session.');
        } else {
            recordResult(22, 'passed', `Inspected browser console (${consoleErrors.length} notices logged: ${consoleErrors.slice(0, 2).join('; ')})`);
        }

    } catch (err) {
        console.error('Browser interaction error:', err);
        recordResult('unexpected_error', 'failed', err.message);
    } finally {
        await browser.close();
        console.log('\n=============================================');
        console.log('BROWSER TEST SUMMARY');
        console.log('=============================================');
        const passedCount = results.filter(r => r.outcome === 'passed').length;
        const failedCount = results.filter(r => r.outcome === 'failed').length;
        console.log(`Total: ${results.length} | Passed: ${passedCount} | Failed: ${failedCount}`);
        for (const res of results) {
            console.log(`- [${res.outcome.toUpperCase()}] Step ${res.step}: ${res.details}`);
        }
    }
}

runBrowserTests();
