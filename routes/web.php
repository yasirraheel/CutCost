<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminBrandsController;
use App\Http\Controllers\AdminBrandFaqsController;
use App\Http\Controllers\AdminCategoriesController;
use App\Http\Controllers\AdminCountriesController;
use App\Http\Controllers\AdminCurrenciesController;
use App\Http\Controllers\AdminFaqsController;
use App\Http\Controllers\AdminFeaturesController;
use App\Http\Controllers\AdminFooterLinkGroupsController;
use App\Http\Controllers\AdminFooterLinksController;
use App\Http\Controllers\AdminHowItWorksStepsController;
use App\Http\Controllers\AdminOffersController;
use App\Http\Controllers\AdminPagesController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdminPressItemsController;
use App\Http\Controllers\AdminReassuranceController;
use App\Http\Controllers\AdminSiteSettingsController;
use App\Http\Controllers\AdminTemplatesController;
use App\Http\Controllers\AdminTestimonialsController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CapturedPageController;
use App\Http\Controllers\CardsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\CreateSubscriptionController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/en/', 301));
Route::get('/en', [CapturedPageController::class, 'show'])->defaults('page', 'en/index.html')->name('home');
Route::get('/en/', [CapturedPageController::class, 'show'])->defaults('page', 'en/index.html');
Route::get('/en/index', [CapturedPageController::class, 'show'])->defaults('page', 'en/index.html');
Route::get('/en/index.html', fn () => redirect('/en', 301));

Route::get('/en/subscriptions', [CapturedPageController::class, 'show'])
    ->defaults('page', 'en/subscriptions.html')
    ->name('subscriptions');
Route::get('/en/subscriptions/house', [CapturedPageController::class, 'show'])
    ->defaults('page', 'en/subscriptions/house.html');
Route::get('/en/brand/amazon', [CapturedPageController::class, 'showBrand'])
    ->defaults('slug', 'amazon');
Route::get('/en/brand/{slug}', [CapturedPageController::class, 'showBrand'])->where('slug', '[A-Za-z0-9][A-Za-z0-9-]*');
Route::get('/en/subscriptions.html', fn () => redirect('/en/subscriptions', 301));
Route::get('/en/subscriptions/house.html', fn () => redirect('/en/subscriptions/house', 301));
Route::get('/en/brand/amazon.html', fn () => redirect('/en/brand/amazon', 301));

Route::get('/cms/en', [CmsPageController::class, 'show'])->defaults('page', 'index.html');
Route::get('/cms/en/team', [CmsPageController::class, 'show'])->defaults('page', 'team/index.html');
Route::get('/cms/en/legal', [CmsPageController::class, 'show'])->defaults('page', 'legal/index.html');
Route::get('/cms/en/legal/tos', [CmsPageController::class, 'show'])->defaults('page', 'legal/tos/index.html');
Route::get('/cms/en/buyer-protection-program', [CmsPageController::class, 'show'])
    ->defaults('page', 'buyer-protection-program/index.html');
Route::get('/cms/en/duolingo-family-plan-cost-current-discounts-on-duolingo-super-max', [CmsPageController::class, 'show'])
    ->defaults('page', 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max/index.html');
Route::get('/cms/en/{page}', [CmsPageController::class, 'show'])->where('page', '[A-Za-z0-9][A-Za-z0-9-]*');

Route::middleware('guest')->group(function (): void {
    Route::get('/en/userlogin', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/en/userlogin.html', fn () => redirect('/en/userlogin', 301));
    Route::post('/en/userlogin', [AuthController::class, 'login']);

    Route::get('/en/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/en/register.html', fn () => redirect('/en/register', 301));
    Route::post('/en/register', [AuthController::class, 'register']);
});

Route::post('/en/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::post('/en/my-settings/security', [SettingsController::class, 'updatePassword'])
        ->name('user.settings.password');
    Route::post('/en/my-settings/preferences', [SettingsController::class, 'updatePreferences'])
        ->name('user.settings.preferences');
    Route::post('/en/my-settings/device/revoke', [SettingsController::class, 'revokeDevice'])
        ->name('user.settings.device.revoke');
    Route::post('/en/my-settings/delete', [SettingsController::class, 'deleteAccount'])
        ->name('user.settings.delete');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/en/my-account/identity', [KycController::class, 'show'])->name('user.identity');
    Route::post('/en/my-account/identity', [KycController::class, 'submit']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('/en/my-documents', [DocumentsController::class, 'show'])->name('user.documents');
    Route::get('/en/my-wallet', [WalletController::class, 'show'])->name('user.wallet');
    Route::get('/en/my-cards', [CardsController::class, 'show'])->name('user.cards');
    Route::post('/en/my-cards/set-default', [CardsController::class, 'setDefault'])->name('user.cards.set-default');
    Route::post('/en/my-cards/delete', [CardsController::class, 'destroy'])->name('user.cards.delete');
    Route::get('/en/my-bank-account', [BankAccountController::class, 'show'])->name('user.bank-account');
    Route::post('/en/my-bank-account/save', [BankAccountController::class, 'save'])->name('user.bank-account.save');
});

Route::middleware(['auth', 'role:admin'])->prefix('en/admin')->group(function (): void {
    Route::get('/', [AdminPanelController::class, 'show'])
        ->defaults('section', 'dashboard')
        ->name('admin.dashboard');
    Route::get('/account/identity', [AdminPanelController::class, 'identity'])
        ->name('admin.account.identity');
    Route::get('/users', [AdminUsersController::class, 'index'])->name('admin.users');
    Route::get('/users/{user}/edit', [AdminUsersController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [AdminUsersController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminUsersController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/kyc', [KycController::class, 'adminIndex'])->name('admin.kyc');
    Route::post('/kyc/{submission}/approve', [KycController::class, 'approve'])->name('admin.kyc.approve');
    Route::post('/kyc/{submission}/reject', [KycController::class, 'reject'])->name('admin.kyc.reject');
    Route::get('/kyc/{submission}/document/{side}', [KycController::class, 'download'])->name('admin.kyc.download');
    Route::resource('brands', AdminBrandsController::class)->names('admin.brands')->parameters(['brands' => 'brand'])->except('show');
    Route::resource('categories', AdminCategoriesController::class)->names('admin.categories')->parameters(['categories' => 'category'])->except('show');
    Route::get('/content/settings', [AdminSiteSettingsController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/content/settings', [AdminSiteSettingsController::class, 'update'])->name('admin.settings.update');
    Route::resource('how-it-works', AdminHowItWorksStepsController::class)->names('admin.how-it-works')->parameters(['how-it-works' => 'step'])->except('show');
    Route::resource('faqs', AdminFaqsController::class)->names('admin.faqs')->parameters(['faqs' => 'faq'])->except('show');
    Route::resource('testimonials', AdminTestimonialsController::class)->names('admin.testimonials')->parameters(['testimonials' => 'testimonial'])->except('show');
    Route::resource('press', AdminPressItemsController::class)->names('admin.press')->parameters(['press' => 'pressItem'])->except('show');
    Route::resource('footer-groups', AdminFooterLinkGroupsController::class)->names('admin.footer-groups')->parameters(['footer-groups' => 'group']);
    Route::get('/footer-groups/{group}/links', [AdminFooterLinksController::class, 'index'])->name('admin.footer-links.index');
    Route::get('/footer-groups/{group}/links/create', [AdminFooterLinksController::class, 'create'])->name('admin.footer-links.create');
    Route::post('/footer-groups/{group}/links', [AdminFooterLinksController::class, 'store'])->name('admin.footer-links.store');
    Route::get('/footer-groups/{group}/links/{link}/edit', [AdminFooterLinksController::class, 'edit'])->name('admin.footer-links.edit');
    Route::put('/footer-groups/{group}/links/{link}', [AdminFooterLinksController::class, 'update'])->name('admin.footer-links.update');
    Route::delete('/footer-groups/{group}/links/{link}', [AdminFooterLinksController::class, 'destroy'])->name('admin.footer-links.destroy');
    Route::resource('features', AdminFeaturesController::class)->names('admin.features')->parameters(['features' => 'feature'])->except('show');
    Route::resource('countries', AdminCountriesController::class)->names('admin.countries')->parameters(['countries' => 'country'])->except('show');
    Route::resource('currencies', AdminCurrenciesController::class)->names('admin.currencies')->parameters(['currencies' => 'currency'])->except('show');
    Route::resource('templates', AdminTemplatesController::class)->names('admin.templates')->parameters(['templates' => 'template'])->except('show');
    Route::resource('reassurance', AdminReassuranceController::class)->names('admin.reassurance')->parameters(['reassurance' => 'reassuranceItem'])->except('show');
    Route::resource('brand-faqs', AdminBrandFaqsController::class)->names('admin.brand-faqs')->parameters(['brand-faqs' => 'brandFaq'])->except('show');
    Route::resource('offers', AdminOffersController::class)->names('admin.offers')->parameters(['offers' => 'offer'])->except('show');
    Route::resource('pages', AdminPagesController::class)->names('admin.pages')->parameters(['pages' => 'page'])->except('show');
    Route::get('/{section}', [AdminPanelController::class, 'show'])
        ->where('section', 'dashboard|shared-subscriptions|joined-subscriptions|waiting-list|wallet|cards|account|settings|chat');
});

$protectedPages = [
    'dashboard' => ['en/dashboard', 'en/dashboard.html'],
    'shared-subscriptions' => ['en/my-shared-subscriptions', 'en/my-shared-subscriptions.html'],
    'joined-subscriptions' => ['en/joined-subscriptions', 'en/joined-subscriptions.html'],
    'waiting-list' => ['en/waiting-list', 'en/waiting-list.html'],
    'account' => ['en/my-account', 'en/my-account.html'],
    'settings' => ['en/my-settings', 'en/my-settings.html'],
];

foreach ($protectedPages as $name => [$canonicalPath, $htmlPath]) {
    Route::get('/'.$canonicalPath, [CapturedPageController::class, 'show'])
        ->defaults('page', $htmlPath)
        ->middleware('auth')
        ->name('user.'.$name);

    Route::get('/'.$htmlPath, fn () => redirect('/'.$canonicalPath, 301))
        ->middleware('auth');

    if ($name === 'account') {
        Route::post('/'.$canonicalPath, [AuthController::class, 'updateAccount'])
            ->middleware('auth')
            ->name('user.account.update');

        Route::post('/'.$htmlPath, [AuthController::class, 'updateAccount'])
            ->middleware('auth');
    }
}

// Chat pages and endpoints (ChatController). Threads are keyed by the
// offer_members row id: the detail page, the messages view and every
// endpoint live under /en/my-chat/subscription/{memberId}/, because
// the demo offers carry no hash for the captured hash based URLs.
Route::middleware('auth')->group(function (): void {
    Route::get('/en/my-chat/subscriptions', [ChatController::class, 'index'])->name('user.chat');
    Route::get('/en/my-chat/subscriptions.html', fn () => redirect('/en/my-chat/subscriptions', 301))
        ->middleware('auth');
    Route::get('/en/my-chat/subscription/{member}', [ChatController::class, 'detail'])
        ->where('member', '[A-Za-z0-9]+')
        ->name('user.chat.detail');
    Route::get('/en/my-chat/subscription/{member}/messages', [ChatController::class, 'messages'])
        ->where('member', '[0-9]+')
        ->name('user.chat.messages');
    Route::get('/en/my-chat/subscription/{identifier}/{member}/messages', [ChatController::class, 'messagesByOffer'])
        ->where(['identifier' => '[A-Za-z0-9]+', 'member' => '[0-9]+'])
        ->name('user.chat.messages.by-offer');
    Route::get('/en/my-chat/unread', [ChatController::class, 'unread'])->name('user.chat.unread');
    Route::get('/en/my-chat/subscription/{member}/messages/latest', [ChatController::class, 'latest'])
        ->where('member', '[0-9]+')
        ->name('user.chat.messages.latest');
    Route::post('/en/my-chat/subscription/{member}/message', [ChatController::class, 'send'])
        ->where('member', '[0-9]+')
        ->name('user.chat.message.send');
    Route::post('/en/my-chat/subscription/{member}/read', [ChatController::class, 'read'])
        ->where('member', '[0-9]+')
        ->name('user.chat.read');
    Route::post('/en/my-chat/subscription/{member}/moderate', [ChatController::class, 'moderate'])
        ->where('member', '[0-9]+')
        ->name('user.chat.moderate');
});

Route::middleware('auth')->group(function (): void {
    // Shared-subscription detail page (Raw Pages 2): detail by offer
    // hash (or id when the offer has no hash), plus the tab POSTs.
    Route::get('/en/my-shared-subscriptions/{identifier}', [CapturedPageController::class, 'showSharedOffer'])
        ->where('identifier', '[A-Za-z0-9]+')
        ->name('user.shared-offer');
    Route::post('/en/my-shared-subscriptions/{identifier}', [CapturedPageController::class, 'saveSharedOfferCredentials'])
        ->where('identifier', '[A-Za-z0-9]+')
        ->name('user.shared-offer.save');
    Route::post('/en/my-shared-subscriptions/{identifier}/mark-email-invite-sent/{member}', [CapturedPageController::class, 'markEmailInviteSent'])
        ->where(['identifier' => '[A-Za-z0-9]+', 'member' => '[0-9]+'])
        ->name('user.shared-offer.mark-invite-sent');
    Route::post('/en/my-shared-subscriptions/{identifier}/migrate', [CapturedPageController::class, 'migrateSharedOffer'])
        ->where('identifier', '[A-Za-z0-9]+')
        ->name('user.shared-offer.migrate');
    Route::post('/en/my-shared-subscriptions/{identifier}/end', [CapturedPageController::class, 'endSharedOffer'])
        ->where('identifier', '[A-Za-z0-9]+')
        ->name('user.shared-offer.end');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/en/create-subscription', [CreateSubscriptionController::class, 'show'])
        ->name('user.create-subscription');
    Route::post('/en/create-subscription/step', [CreateSubscriptionController::class, 'saveStep'])
        ->name('user.create-subscription.step');
    Route::post('/en/create-subscription', [CreateSubscriptionController::class, 'publish'])
        ->name('user.create-subscription.store');
    Route::get('/en/get-brands', [CreateSubscriptionController::class, 'getBrands'])
        ->name('user.get-brands');
    Route::get('/en/get-templates', [CreateSubscriptionController::class, 'getTemplates'])
        ->name('user.get-templates');
    Route::post('/en/create-subscription/draft', [CreateSubscriptionController::class, 'storeDraft'])
        ->name('user.create-subscription.draft');
    Route::post('/en/create-subscription/draft/check', [CreateSubscriptionController::class, 'checkDraft'])
        ->name('user.create-subscription.draft.check');
    Route::post('/en/create-subscription/check-brand', [CreateSubscriptionController::class, 'checkBrand'])
        ->name('user.create-subscription.check-brand');
    Route::get('/en/create-subscription/{offer}/edit', [CreateSubscriptionController::class, 'edit'])
        ->name('user.create-subscription.edit');
    Route::put('/en/create-subscription/{offer}', [CreateSubscriptionController::class, 'update'])
        ->name('user.create-subscription.update');
    Route::delete('/en/create-subscription/{offer}', [CreateSubscriptionController::class, 'destroy'])
        ->name('user.create-subscription.destroy');
});

// Keep all supplied captured pages routable while avoiding a new design layer.
Route::get('/{path}', [CapturedPageController::class, 'fallback'])->where('path', '.*');
