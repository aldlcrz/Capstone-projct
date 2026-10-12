<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScrollPositionRestorationTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function scroll_restoration_component_exists_and_contains_expected_engine_logic()
    {
        $this->assertFileExists(resource_path('views/components/scroll-position-restoration.blade.php'));
        $content = file_get_contents(resource_path('views/components/scroll-position-restoration.blade.php'));

        $this->assertStringContainsString('lumbarong_scroll_pos:', $content);
        $this->assertStringContainsString('detectNavigationType', $content);
        $this->assertStringContainsString('getMainContainer', $content);
        $this->assertStringContainsString('#superadmin-main, #admin-main, #seller-main', $content);
        $this->assertStringContainsString('ResizeObserver', $content);
        $this->assertStringContainsString('handleUserInterrupt', $content);
        $this->assertStringContainsString('requestAnimationFrame', $content);
        $this->assertStringContainsString('sessionStorage.removeItem', $content);

        // Active tab, wizard step, and progress preservation assertions
        $this->assertStringContainsString('isTabKey', $content);
        $this->assertStringContainsString('isStepKey', $content);
        $this->assertStringContainsString('collectAlpineState', $content);
        $this->assertStringContainsString('restoreAlpineState', $content);
        $this->assertStringContainsString('activetab', $content);
        $this->assertStringContainsString('statusfilter', $content);
    }

    /** @test */
    public function modal_scroll_lock_preserves_scroll_coordinates_on_lock_and_unlock()
    {
        $this->assertFileExists(resource_path('views/components/modal-scroll-lock.blade.php'));
        $content = file_get_contents(resource_path('views/components/modal-scroll-lock.blade.php'));

        $this->assertStringContainsString('lumbarongLockedWinY', $content);
        $this->assertStringContainsString('lumbarongLockedScrollY', $content);
        $this->assertStringContainsString('setTimeout(toggleBodyScrollLock', $content);
    }

    /** @test */
    public function welcome_view_prevents_auto_scroll_to_catalogue_on_reload()
    {
        $content = file_get_contents(resource_path('views/welcome.blade.php'));
        $this->assertStringContainsString('isReloadNav', $content);
        $this->assertStringContainsString('!isReloadNav', $content);
    }

    /** @test */
    public function app_layout_includes_scroll_position_restoration_component()
    {
        $content = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('<x-scroll-position-restoration />', $content);
    }

    /** @test */
    public function seller_layout_includes_scroll_position_restoration_component()
    {
        $content = file_get_contents(resource_path('views/layouts/seller.blade.php'));
        $this->assertStringContainsString('<x-scroll-position-restoration />', $content);
    }

    /** @test */
    public function admin_layout_includes_scroll_position_restoration_component()
    {
        $content = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $this->assertStringContainsString('<x-scroll-position-restoration />', $content);
    }

    /** @test */
    public function superadmin_layout_includes_scroll_position_restoration_component()
    {
        $content = file_get_contents(resource_path('views/layouts/superadmin.blade.php'));
        $this->assertStringContainsString('<x-scroll-position-restoration />', $content);
    }

    /** @test */
    public function home_page_renders_with_scroll_restoration_script()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('lumbarong_scroll_pos:', false);
    }

    /** @test */
    public function seller_registration_view_includes_scroll_position_restoration_component()
    {
        $content = file_get_contents(resource_path('views/auth/seller-register.blade.php'));
        $this->assertStringContainsString('<x-scroll-position-restoration />', $content);
        $this->assertStringContainsString('seller_reg_step', $content);
    }
}
