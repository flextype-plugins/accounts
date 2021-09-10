<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype\Plugins\Accounts\Controllers;

use Flextype\Component\Arrays\Arrays;
use Flextype\Component\Filesystem\Filesystem;

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Http\Environment;
use Slim\Http\Uri;
use const PASSWORD_BCRYPT;
use function array_merge;
use function bin2hex;
use function date;
use function Flextype\Component\I18n\__;
use function password_hash;
use function password_verify;
use function random_bytes;
use function strtr;
use function time;
use function trim;

class AccountsController
{
    /**
     * Index page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function index(Request $request, Response $response, array $args) : Response
    {
        if (registry()->get('plugins.accounts.settings.index.enabled') === false) {

            $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return twig()->render($response, $template_path);
        }

        $accounts_list = Filesystem::listContents(PATH['project'] . '/accounts');
        $accounts      = [];

        foreach ($accounts_list as $account) {
            if ($account['type'] !== 'dir' || ! Filesystem::has($account['path'] . '/' . 'profile.yaml')) {
                continue;
            }

            $account_to_store = serializers()->yaml()->decode(Filesystem::read($account['path'] . '/profile.yaml'));

            $_path = explode('/', $account['path']);
            $account_to_store['email'] = array_pop($_path);

            Arrays::delete($account, 'password');
            Arrays::delete($account, 'hashed_password');
            Arrays::delete($account, 'hashed_password_reset');


            $accounts[] = $account_to_store;
        }

        $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/index.html';
        $plugin_template_path = 'plugins/accounts/templates/index.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return twig()->render($response, $template_path, ['accounts' => $accounts]);
    }

    /**
     * Login page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function login(Request $request, Response $response, array $args) : Response
    {
        if (registry()->get('plugins.accounts.settings.login.enabled') === false) {

            $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return twig()->render($response, $template_path);
        }

        if (acl()->isUserLoggedIn()) {
            return $response->withRedirect(urlFor('accounts.profile', ['email' => acl()->getUserLoggedInEmail()]));
        }

        $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/login.html';
        $plugin_template_path = 'plugins/accounts/templates/login.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return twig()->render($response, $template_path);
    }

    /**
     * Login page proccess
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function loginProcess(Request $request, Response $response, array $args) : Response
    {
        // Get Data from POST
        $post_data = $request->getParsedBody();

        $email = $post_data['email'];

        if (Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {

            $user_file = serializers()->yaml()->decode(Filesystem::read($_user_file), false);

            if (password_verify(trim($post_data['password']), $user_file['hashed_password'])) {

                acl()->setUserLoggedInEmail($email);
                acl()->setUserLoggedInRoles($user_file['roles']);
                acl()->setUserLoggedInUuid($user_file['uuid']);
                acl()->setUserLoggedIn(true);

                // Run event onAccountsUserLoggedIn
                emitter()->emit('onAccountsUserLoggedIn');

                // Add redirect to route name or to specific link
                if (registry()->get('plugins.accounts.settings.login.redirect.route')) {
                    if (registry()->get('plugins.accounts.settings.login.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.login.redirect.route.name'), ['email' => $email]));
                    }

                    if (registry()->get('plugins.accounts.settings.login.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.login.redirect.route.name'), ['email' => $email]));
                    }

                    return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.login.redirect.route.name')));
                } else {
                    return $response->withRedirect(registry()->get('plugins.accounts.settings.login.redirect.link'));
                }
            }

            container()->get('flash')->addMessage('error', __('accounts_message_wrong_email_password'));

            return redirect('accounts.login');

        }

        container()->get('flash')->addMessage('error', __('accounts_message_wrong_email_password'));

        return redirect('accounts.login');
    }

    /**
     * New passoword process
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function newPasswordProcess(Request $request, Response $response, array $args) : Response
    {
        $email = $args['email'];

        if (Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {
            $user_file_body = Filesystem::read($_user_file);
            $user_file_data = serializers()->yaml()->decode($user_file_body);

            if (is_null($user_file_data['hashed_password_reset'])) {
                container()->get('flash')->addMessage('error', __('accounts_message_hashed_password_reset_not_valid'));
                return redirect('accounts.login');
            }

            if (password_verify(trim($args['hash']), $user_file_data['hashed_password_reset'])) {

                // Generate new passoword
                $raw_password    = bin2hex(random_bytes(16));
                $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

                $user_file_data['hashed_password'] = $hashed_password;

                Arrays::delete($user_file_data, 'hashed_password_reset');

                if (Filesystem::write(
                    PATH['project'] . '/accounts/' . $email . '/profile.yaml',
                    serializers()->yaml()->encode(
                        $user_file_data
                    )
                )) {
                    try {

                        // Instantiation and passing `true` enables exceptions
                        $mail = new PHPMailer(true);

                        $theme_new_password_email_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/emails/new-password.md';
                        $plugin_new_password_email_path = 'plugins/accounts/templates/emails/new-password.md';
                        $email_template_path            = Filesystem::has(PATH['project'] . '/' . $theme_new_password_email_path) ? $theme_new_password_email_path : $plugin_new_password_email_path;

                        $new_password_email = serializers()->frontmatter()->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path));

                        //Recipients
                        $mail->setFrom(registry()->get('plugins.accounts.settings.from.email'), registry()->get('plugins.accounts.settings.from.name'));
                        $mail->addAddress($email, $email);

                        if (registry()->has('flextype.settings.url') && registry()->get('flextype.settings.url') !== '') {
                            $url = registry()->get('flextype.settings.url');
                        } else {
                            $url = Uri::createFromEnvironment(new Environment($_SERVER))->getBaseUrl();
                        }

                        if (isset($user_file_data['full_name'])) {
                            $user = $user_file_data['full_name'];
                        } else {
                            $user = $email;
                        }

                        $tags = [
                            '{sitename}' => registry()->get('plugins.site.settings.title'),
                            '{email}' => $email,
                            '{user}' => $user,
                            '{password}' => $raw_password,
                            '{url}' => $url,
                        ];

                        $subject = parsers()->shortcodes()->process($new_password_email['subject']);
                        $content = parsers()->markdown()->parse(parsers()->shortcodes()->process($new_password_email['content']));

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = strtr($subject, $tags);
                        $mail->Body    = strtr($content, $tags);

                        // Send email
                        $mail->send();

                    } catch (\Exception $e) {

                    }

                    container()->get('flash')->addMessage('success', __('accounts_message_new_password_was_sended'));

                    // Run event onAccountsNewPasswordSended
                    emitter()->emit('onAccountsNewPasswordSended');

                    // Add redirect to route name or to specific link
                    if (registry()->get('plugins.accounts.settings.new_password.redirect.route')) {
                        return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.new_password.redirect.route.name')));
                    } else {
                        return $response->withRedirect(registry()->get('plugins.accounts.settings.new_password.redirect.link'));
                    }
                }
                return redirect('accounts.login');
            }

            container()->get('flash')->addMessage('error', __('accounts_message_hashed_password_reset_not_valid'));

            return redirect('accounts.login');
        }

        return redirect('accounts.login');
    }

    /**
     * Reset passoword page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function resetPassword(Request $request, Response $response, array $args) : Response
    {
        if (registry()->get('plugins.accounts.settings.reset_password.enabled') === false) {

            $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return twig()->render($response, $template_path);
        }

        $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/registration.html';
        $plugin_template_path = 'plugins/accounts/templates/reset-password.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return twig()->render($response, $template_path);
    }

    /**
     * Reset passoword process
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function resetPasswordProcess(Request $request, Response $response, array $args) : Response
    {
        // Get Data from POST
        $post_data = $request->getParsedBody();

        // Get email
        $email = $post_data['email'];

        if (Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {
            Arrays::delete($post_data, '__csrf_token');
            
            Arrays::delete($post_data, 'form-save-action');
            Arrays::delete($post_data, 'email');

            $raw_hash                           = bin2hex(random_bytes(16));
            $post_data['hashed_password_reset'] = password_hash($raw_hash, PASSWORD_BCRYPT);

            $user_file_body = Filesystem::read($_user_file);
            $user_file_data = serializers()->yaml()->decode($user_file_body);

            // Create account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $email . '/profile.yaml',
                serializers()->yaml()->encode(
                    array_merge($user_file_data, $post_data)
                )
            )) {
                try {

                    // Instantiation and passing `true` enables exceptions
                    $mail = new PHPMailer(true);

                    $theme_reset_password_email_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/emails/reset-password.md';
                    $plugin_reset_password_email_path = 'plugins/accounts/templates/emails/reset-password.md';
                    $email_template_path              = Filesystem::has(PATH['project'] . '/' . $theme_reset_password_email_path) ? $theme_reset_password_email_path : $plugin_reset_password_email_path;

                    $reset_password_email = serializers()->frontmatter()->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path));

                    //Recipients
                    $mail->setFrom(registry()->get('plugins.accounts.settings.from.email'), registry()->get('plugins.accounts.settings.from.name'));
                    $mail->addAddress($email, $email);

                    if (registry()->has('flextype.settings.url') && registry()->get('flextype.settings.url') !== '') {
                        $url = registry()->get('flextype.settings.url');
                    } else {
                        $url = Uri::createFromEnvironment(new Environment($_SERVER))->getBaseUrl();
                    }

                    if (isset($user_file_data['full_name'])) {
                        $user = $user_file_data['full_name'];
                    } else {
                        $user = $email;
                    }

                    $tags = [
                        '{sitename}' => registry()->get('plugins.site.settings.title'),
                        '{email}' => $email,
                        '{user}' => $user,
                        '{url}' => $url,
                        '{new_hash}' => $raw_hash,
                    ];

                    $subject = parsers()->shortcodes()->process($reset_password_email['subject']);
                    $content = parsers()->markdown()->parse(parsers()->shortcodes()->process($reset_password_email['content']));

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = strtr($subject, $tags);
                    $mail->Body    = strtr($content, $tags);

                    // Send email
                    $mail->send();

                } catch (\Exception $e) {

                }

                // Run event onAccountsPasswordReset
                emitter()->emit('onAccountsPasswordReset');

                // Add redirect to route name or to specific link
                if (registry()->get('plugins.accounts.settings.reset_password.redirect.route')) {
                    return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.reset_password.redirect.route.name')));
                } else {
                    return $response->withRedirect(registry()->get('plugins.accounts.settings.reset_password.redirect.link'));
                }
            }

            return redirect('accounts.login');
        }

        return redirect('accounts.login');
    }

    /**
     * Registration page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function registration(Request $request, Response $response, array $args) : Response
    {
        if (registry()->get('plugins.accounts.settings.registration.enabled') === false) {

            $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return twig()->render($response, $template_path);
        }

        if (acl()->isUserLoggedIn()) {
            return $response->withRedirect(urlFor('accounts.profile', ['email' => acl()->getUserLoggedInEmail()]));
        }

        $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/registration.html';
        $plugin_template_path = 'plugins/accounts/templates/registration.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return twig()->render($response, $template_path);
    }

    /**
     * Registration page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function registrationProcess(Request $request, Response $response, array $args) : Response
    {
        // Get Data from POST
        $post_data = $request->getParsedBody();

        // Get user email
        $email = $post_data['email'];

        if (! Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {

            // Generate UUID
            $uuid = Uuid::uuid4()->toString();

            // Get time
            $time = date(registry()->get('flextype.settings.date_format'), time());

            // Get hashed password
            $hashed_password = password_hash($post_data['password'], PASSWORD_BCRYPT);

            // Data
            $data = [];
            $data['registered_at']   = $time;
            $data['uuid']            = $uuid;
            $data['hashed_password'] = $hashed_password;
            $data['roles']           = registry()->get('plugins.accounts.settings.registration.default_roles');
            $data['state']           = registry()->get('plugins.accounts.settings.registration.default_state');

            // Delete fields from POST DATA
            Arrays::delete($post_data, 'email');
            Arrays::delete($post_data, '__csrf_token');
            
            Arrays::delete($post_data, 'password');
            Arrays::delete($post_data, 'form-save-action');

            // Create accounts directory and account
            Filesystem::createDir(PATH['project'] . '/accounts/' . $email);

            // Create admin account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $email . '/profile.yaml',
                serializers()->yaml()->encode(
                    array_merge($post_data, $data)
                )
            )) {
                try {

                    // Instantiation and passing `true` enables exceptions
                    $mail = new PHPMailer(true);

                    $theme_new_user_email_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/emails/new-user.md';
                    $plugin_new_user_email_path = 'plugins/accounts/templates/emails/new-user.md';
                    $email_template_path        = Filesystem::has(PATH['project'] . '/' . $theme_new_user_email_path) ? $theme_new_user_email_path : $plugin_new_user_email_path;

                    $new_user_email = serializers()->frontmatter()->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path));

                    //Recipients
                    $mail->setFrom(registry()->get('plugins.accounts.settings.from.email'), registry()->get('plugins.accounts.settings.from.name'));
                    $mail->addAddress($email, $email);

                    if (isset($post_data['full_name'])) {
                        $user = $post_data['full_name'];
                    } else {
                        $user = $email;
                    }

                    $tags = [
                        '{sitename}' => registry()->get('plugins.site.settings.title'),
                        '{user}'    => $user,
                    ];

                    $subject = parsers()->shortcodes()->process($new_user_email['subject']);
                    $content = parsers()->markdown()->parse(parsers()->shortcodes()->process($new_user_email['content']));

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = strtr($subject, $tags);
                    $mail->Body    = strtr($content, $tags);

                    // Send email
                    $mail->send();

                } catch (\Exception $e) {

                }

                // Run event onAccountsNewUserRegistered
                emitter()->emit('onAccountsNewUserRegistered');

                // Add redirect to route name or to specific link
                if (registry()->get('plugins.accounts.settings.registration.redirect.route')) {
                    if (registry()->get('plugins.accounts.settings.registration.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.registration.redirect.route.name'), ['email' => $user_file['email']]));
                    }

                    if (registry()->get('plugins.accounts.settings.registration.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.registration.redirect.route.name'), ['email' => $user_file['email']]));
                    }

                    return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.registration.redirect.route.name')));
                } else {
                    return $response->withRedirect(registry()->get('plugins.accounts.settings.registration.redirect.link'));
                }
            }

            return redirect('accounts.registration');
        }

        return redirect('accounts.registration');
    }

    /**
     * Profile page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function profile(Request $request, Response $response, array $args) : Response
    {
        if (registry()->get('plugins.accounts.settings.profile.enabled') === false) {

            $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return twig()->render($response, $template_path);
        }

        $email = $args['email'];

        // Redirect to accounts index if profile not founded
        if (!Filesystem::has(PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {
            return redirect('accounts.index');
        }

        $profile = serializers()->yaml()->decode(Filesystem::read(PATH['project'] . '/accounts/' . $email . '/profile.yaml'));
        $profile['email'] = $email;

        Arrays::delete($profile, 'uuid');
        Arrays::delete($profile, 'password');
        Arrays::delete($profile, 'hashed_password');
        Arrays::delete($profile, 'hashed_password_reset');
        Arrays::delete($profile, 'roles');

        $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/profile.html';
        $plugin_template_path = 'plugins/accounts/templates/profile.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return twig()->render(
            $response,
            $template_path,
            ['profile' => $profile]
        );
    }

    /**
     * Profile edit page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function profileEdit(Request $request, Response $response, array $args) : Response
    {
        if (registry()->get('plugins.accounts.settings.profile_edit.enabled') === false) {

            $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return twig()->render($response, $template_path);
        }

        $email = $args['email'];

        // Redirect to accounts index if profile not founded
        if (!Filesystem::has(PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {
            return redirect('accounts.index');
        }

        $profile = serializers()->yaml()->decode(Filesystem::read(PATH['project'] . '/accounts/' . $email . '/profile.yaml'));

        $theme_template_path  = 'themes/' . registry()->get('plugins.site.settings.theme') . '/templates/accounts/templates/profile-edit.html';
        $plugin_template_path = 'plugins/accounts/templates/profile-edit.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        $profile['email'] = $email;

        if ($email === acl()->getUserLoggedInEmail()) {
            Arrays::delete($profile, 'uuid');
            Arrays::delete($profile, 'hashed_password');
            Arrays::delete($profile, 'roles');

            return twig()->render($response, $template_path, ['profile' => $profile]);
        }

        return $response->withRedirect(urlFor('accounts.profile', ['email' => acl()->getUserLoggedInEmail()]));
    }

    /**
     * Profile edit page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function profileEditProcess(Request $request, Response $response, array $args) : Response
    {
        // Get Data from POST
        $post_data = $request->getParsedBody();

        // Get email
        $email = $args['email'];

        // Set current user profile path
        $current_user_profile_path = PATH['project'] . '/accounts/' . $email . '/profile.yaml';

        // Set user profile path
        $user_profile_path = $current_user_profile_path;

        if (Filesystem::has($user_profile_path)) {
            Arrays::delete($post_data, '__csrf_token');
            
            Arrays::delete($post_data, 'form-save-action');
            Arrays::delete($post_data, 'password');
            Arrays::delete($post_data, 'email');

            if (! empty($post_data['new_password'])) {
                $post_data['hashed_password'] = password_hash($post_data['new_password'], PASSWORD_BCRYPT);
                Arrays::delete($post_data, 'new_password');
            } else {
                Arrays::delete($post_data, 'password');
                Arrays::delete($post_data, 'new_password');
            }

            $user_file_body = Filesystem::read($user_profile_path);
            $user_file_data = serializers()->yaml()->decode($user_file_body);

            // Create admin account
            if (Filesystem::write(
                $user_profile_path,
                serializers()->yaml()->encode(
                    array_merge($user_file_data, $post_data)
                )
            )) {

                // Run event onAccountsProfileEdited
                emitter()->emit('onAccountsProfileEdited');

                // Add redirect to route name or to specific link
                if (registry()->get('plugins.accounts.settings.profile_edit.redirect.route')) {
                    if (registry()->get('plugins.accounts.settings.profile_edit.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.profile_edit.redirect.route.name'), ['email' => acl()->getUserLoggedInEmail()]));
                    }

                    if (registry()->get('plugins.accounts.settings.profile_edit.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.profile_edit.redirect.route.name'), ['email' => acl()->getUserLoggedInEmail()]));
                    }

                    return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.profile_edit.redirect.route.name')));
                } else {
                    return $response->withRedirect(registry()->get('plugins.accounts.settings.profile_edit.redirect.link'));
                }
            }

            return redirect('accounts.registration');
        }

        return redirect('accounts.registration');
    }

    /**
     * Logout page process
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     */
    public function logoutProcess(Request $request, Response $response) : Response
    {
        session()->destroy();

        // Run event onAccountsLogout
        emitter()->emit('onAccountsLogout');

        // Add redirect to route name or to specific link
        if (registry()->get('plugins.accounts.settings.logout.redirect.route')) {
            return $response->withRedirect(urlFor(registry()->get('plugins.accounts.settings.logout.redirect.route.name')));
        } else {
            return $response->withRedirect(registry()->get('plugins.accounts.settings.logout.redirect.link'));
        }
    }
}
