<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace Flextype\Plugin\Accounts\Controllers;

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
    * Flextype Application
    */


   /**
    * __construct
    */
    public function __construct()
    {

    }

    /**
     * Index page
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     * @param array    $args     Args
     */
    public function index(Request $request, Response $response, array $args) : Response
    {
        if (flextype('registry')->get('plugins.accounts.settings.index.enabled') === false) {

            $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return flextype('twig')->render($response, $template_path);
        }

        $accounts_list = Filesystem::listContents(PATH['project'] . '/accounts');
        $accounts      = [];

        foreach ($accounts_list as $account) {
            if ($account['type'] !== 'dir' || ! Filesystem::has($account['path'] . '/' . 'profile.yaml')) {
                continue;
            }

            $account_to_store = flextype('serializers')->yaml()->decode(Filesystem::read($account['path'] . '/profile.yaml'));

            $_path = explode('/', $account['path']);
            $account_to_store['email'] = array_pop($_path);

            Arrays::delete($account, 'password');
            Arrays::delete($account, 'hashed_password');
            Arrays::delete($account, 'hashed_password_reset');


            $accounts[] = $account_to_store;
        }

        $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/index.html';
        $plugin_template_path = 'plugins/accounts/templates/index.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return flextype('twig')->render($response, $template_path, ['accounts' => $accounts]);
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
        if (flextype('registry')->get('plugins.accounts.settings.login.enabled') === false) {

            $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return flextype('twig')->render($response, $template_path);
        }

        if (flextype('acl')->isUserLoggedIn()) {
            return $response->withRedirect(flextype('router')->pathFor('accounts.profile', ['email' => flextype('acl')->getUserLoggedInEmail()]));
        }

        $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/login.html';
        $plugin_template_path = 'plugins/accounts/templates/login.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return flextype('twig')->render($response, $template_path);
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

            $user_file = flextype('serializers')->yaml()->decode(Filesystem::read($_user_file), false);

            if (password_verify(trim($post_data['password']), $user_file['hashed_password'])) {

                flextype('acl')->setUserLoggedInEmail($email);
                flextype('acl')->setUserLoggedInRoles($user_file['roles']);
                flextype('acl')->setUserLoggedInUuid($user_file['uuid']);
                flextype('acl')->setUserLoggedIn(true);

                // Run event onAccountsUserLoggedIn
                flextype('emitter')->emit('onAccountsUserLoggedIn');

                // Add redirect to route name or to specific link
                if (flextype('registry')->get('plugins.accounts.settings.login.redirect.route')) {
                    if (flextype('registry')->get('plugins.accounts.settings.login.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.login.redirect.route.name'), ['email' => $email]));
                    }

                    if (flextype('registry')->get('plugins.accounts.settings.login.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.login.redirect.route.name'), ['email' => $email]));
                    }

                    return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.login.redirect.route.name')));
                } else {
                    return $response->withRedirect(flextype('registry')->get('plugins.accounts.settings.login.redirect.link'));
                }
            }

            flextype('flash')->addMessage('error', __('accounts_message_wrong_email_password'));

            return $response->withRedirect(flextype('router')->pathFor('accounts.login'));

        }

        flextype('flash')->addMessage('error', __('accounts_message_wrong_email_password'));

        return $response->withRedirect(flextype('router')->pathFor('accounts.login'));
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
            $user_file_data = flextype('serializers')->yaml()->decode($user_file_body);

            if (is_null($user_file_data['hashed_password_reset'])) {
                flextype('flash')->addMessage('error', __('accounts_message_hashed_password_reset_not_valid'));
                return $response->withRedirect(flextype('router')->pathFor('accounts.login'));
            }

            if (password_verify(trim($args['hash']), $user_file_data['hashed_password_reset'])) {

                // Generate new passoword
                $raw_password    = bin2hex(random_bytes(16));
                $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

                $user_file_data['hashed_password'] = $hashed_password;

                Arrays::delete($user_file_data, 'hashed_password_reset');

                if (Filesystem::write(
                    PATH['project'] . '/accounts/' . $email . '/profile.yaml',
                    flextype('serializers')->yaml()->encode(
                        $user_file_data
                    )
                )) {
                    try {

                        // Instantiation and passing `true` enables exceptions
                        $mail = new PHPMailer(true);

                        $theme_new_password_email_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/emails/new-password.md';
                        $plugin_new_password_email_path = 'plugins/accounts/templates/emails/new-password.md';
                        $email_template_path            = Filesystem::has(PATH['project'] . '/' . $theme_new_password_email_path) ? $theme_new_password_email_path : $plugin_new_password_email_path;

                        $new_password_email = flextype('serializers')->frontmatter()->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path));

                        //Recipients
                        $mail->setFrom(flextype('registry')->get('plugins.accounts.settings.from.email'), flextype('registry')->get('plugins.accounts.settings.from.name'));
                        $mail->addAddress($email, $email);

                        if (flextype('registry')->has('flextype.settings.url') && flextype('registry')->get('flextype.settings.url') !== '') {
                            $url = flextype('registry')->get('flextype.settings.url');
                        } else {
                            $url = Uri::createFromEnvironment(new Environment($_SERVER))->getBaseUrl();
                        }

                        if (isset($user_file_data['full_name'])) {
                            $user = $user_file_data['full_name'];
                        } else {
                            $user = $email;
                        }

                        $tags = [
                            '{sitename}' => flextype('registry')->get('plugins.site.settings.title'),
                            '{email}' => $email,
                            '{user}' => $user,
                            '{password}' => $raw_password,
                            '{url}' => $url,
                        ];

                        $subject = flextype('parsers')->shortcode()->process($new_password_email['subject']);
                        $content = flextype('parsers')->markdown()->parse(flextype('parsers')->shortcode()->process($new_password_email['content']));

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = strtr($subject, $tags);
                        $mail->Body    = strtr($content, $tags);

                        // Send email
                        $mail->send();

                    } catch (\Exception $e) {

                    }

                    flextype('flash')->addMessage('success', __('accounts_message_new_password_was_sended'));

                    // Run event onAccountsNewPasswordSended
                    flextype('emitter')->emit('onAccountsNewPasswordSended');

                    // Add redirect to route name or to specific link
                    if (flextype('registry')->get('plugins.accounts.settings.new_password.redirect.route')) {
                        return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.new_password.redirect.route.name')));
                    } else {
                        return $response->withRedirect(flextype('registry')->get('plugins.accounts.settings.new_password.redirect.link'));
                    }
                }
                return $response->withRedirect(flextype('router')->pathFor('accounts.login'));
            }

            flextype('flash')->addMessage('error', __('accounts_message_hashed_password_reset_not_valid'));

            return $response->withRedirect(flextype('router')->pathFor('accounts.login'));
        }

        return $response->withRedirect(flextype('router')->pathFor('accounts.login'));
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
        if (flextype('registry')->get('plugins.accounts.settings.reset_password.enabled') === false) {

            $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return flextype('twig')->render($response, $template_path);
        }

        $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/registration.html';
        $plugin_template_path = 'plugins/accounts/templates/reset-password.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return flextype('twig')->render($response, $template_path);
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
            Arrays::delete($post_data, 'csrf_name');
            Arrays::delete($post_data, 'csrf_value');
            Arrays::delete($post_data, 'form-save-action');
            Arrays::delete($post_data, 'email');

            $raw_hash                           = bin2hex(random_bytes(16));
            $post_data['hashed_password_reset'] = password_hash($raw_hash, PASSWORD_BCRYPT);

            $user_file_body = Filesystem::read($_user_file);
            $user_file_data = flextype('serializers')->yaml()->decode($user_file_body);

            // Create account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $email . '/profile.yaml',
                flextype('serializers')->yaml()->encode(
                    array_merge($user_file_data, $post_data)
                )
            )) {
                try {

                    // Instantiation and passing `true` enables exceptions
                    $mail = new PHPMailer(true);

                    $theme_reset_password_email_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/emails/reset-password.md';
                    $plugin_reset_password_email_path = 'plugins/accounts/templates/emails/reset-password.md';
                    $email_template_path              = Filesystem::has(PATH['project'] . '/' . $theme_reset_password_email_path) ? $theme_reset_password_email_path : $plugin_reset_password_email_path;

                    $reset_password_email = flextype('serializers')->frontmatter()->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path));

                    //Recipients
                    $mail->setFrom(flextype('registry')->get('plugins.accounts.settings.from.email'), flextype('registry')->get('plugins.accounts.settings.from.name'));
                    $mail->addAddress($email, $email);

                    if (flextype('registry')->has('flextype.settings.url') && flextype('registry')->get('flextype.settings.url') !== '') {
                        $url = flextype('registry')->get('flextype.settings.url');
                    } else {
                        $url = Uri::createFromEnvironment(new Environment($_SERVER))->getBaseUrl();
                    }

                    if (isset($user_file_data['full_name'])) {
                        $user = $user_file_data['full_name'];
                    } else {
                        $user = $email;
                    }

                    $tags = [
                        '{sitename}' => flextype('registry')->get('plugins.site.settings.title'),
                        '{email}' => $email,
                        '{user}' => $user,
                        '{url}' => $url,
                        '{new_hash}' => $raw_hash,
                    ];

                    $subject = flextype('parsers')->shortcode()->process($reset_password_email['subject']);
                    $content = flextype('parsers')->markdown()->parse(flextype('parsers')->shortcode()->process($reset_password_email['content']));

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = strtr($subject, $tags);
                    $mail->Body    = strtr($content, $tags);

                    // Send email
                    $mail->send();

                } catch (\Exception $e) {

                }

                // Run event onAccountsPasswordReset
                flextype('emitter')->emit('onAccountsPasswordReset');

                // Add redirect to route name or to specific link
                if (flextype('registry')->get('plugins.accounts.settings.reset_password.redirect.route')) {
                    return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.reset_password.redirect.route.name')));
                } else {
                    return $response->withRedirect(flextype('registry')->get('plugins.accounts.settings.reset_password.redirect.link'));
                }
            }

            return $response->withRedirect(flextype('router')->pathFor('accounts.login'));
        }

        return $response->withRedirect(flextype('router')->pathFor('accounts.login'));
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
        if (flextype('registry')->get('plugins.accounts.settings.registration.enabled') === false) {

            $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return flextype('twig')->render($response, $template_path);
        }

        if (flextype('acl')->isUserLoggedIn()) {
            return $response->withRedirect(flextype('router')->pathFor('accounts.profile', ['email' => flextype('acl')->getUserLoggedInEmail()]));
        }

        $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/registration.html';
        $plugin_template_path = 'plugins/accounts/templates/registration.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return flextype('twig')->render($response, $template_path);
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
            $time = date(flextype('registry')->get('flextype.settings.date_format'), time());

            // Get hashed password
            $hashed_password = password_hash($post_data['password'], PASSWORD_BCRYPT);

            // Data
            $data = [];
            $data['registered_at']   = $time;
            $data['uuid']            = $uuid;
            $data['hashed_password'] = $hashed_password;
            $data['roles']           = flextype('registry')->get('plugins.accounts.settings.registration.default_roles');
            $data['state']           = flextype('registry')->get('plugins.accounts.settings.registration.default_state');

            // Delete fields from POST DATA
            Arrays::delete($post_data, 'email');
            Arrays::delete($post_data, 'csrf_name');
            Arrays::delete($post_data, 'csrf_value');
            Arrays::delete($post_data, 'password');
            Arrays::delete($post_data, 'form-save-action');

            // Create accounts directory and account
            Filesystem::createDir(PATH['project'] . '/accounts/' . $email);

            // Create admin account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $email . '/profile.yaml',
                flextype('serializers')->yaml()->encode(
                    array_merge($post_data, $data)
                )
            )) {
                try {

                    // Instantiation and passing `true` enables exceptions
                    $mail = new PHPMailer(true);

                    $theme_new_user_email_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/emails/new-user.md';
                    $plugin_new_user_email_path = 'plugins/accounts/templates/emails/new-user.md';
                    $email_template_path        = Filesystem::has(PATH['project'] . '/' . $theme_new_user_email_path) ? $theme_new_user_email_path : $plugin_new_user_email_path;

                    $new_user_email = flextype('serializers')->frontmatter()->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path));

                    //Recipients
                    $mail->setFrom(flextype('registry')->get('plugins.accounts.settings.from.email'), flextype('registry')->get('plugins.accounts.settings.from.name'));
                    $mail->addAddress($email, $email);

                    if (isset($post_data['full_name'])) {
                        $user = $post_data['full_name'];
                    } else {
                        $user = $email;
                    }

                    $tags = [
                        '{sitename}' => flextype('registry')->get('plugins.site.settings.title'),
                        '{user}'    => $user,
                    ];

                    $subject = flextype('parsers')->shortcode()->process($new_user_email['subject']);
                    $content = flextype('parsers')->markdown()->parse(flextype('parsers')->shortcode()->process($new_user_email['content']));

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = strtr($subject, $tags);
                    $mail->Body    = strtr($content, $tags);

                    // Send email
                    $mail->send();

                } catch (\Exception $e) {

                }

                // Run event onAccountsNewUserRegistered
                flextype('emitter')->emit('onAccountsNewUserRegistered');

                // Add redirect to route name or to specific link
                if (flextype('registry')->get('plugins.accounts.settings.registration.redirect.route')) {
                    if (flextype('registry')->get('plugins.accounts.settings.registration.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.registration.redirect.route.name'), ['email' => $user_file['email']]));
                    }

                    if (flextype('registry')->get('plugins.accounts.settings.registration.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.registration.redirect.route.name'), ['email' => $user_file['email']]));
                    }

                    return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.registration.redirect.route.name')));
                } else {
                    return $response->withRedirect(flextype('registry')->get('plugins.accounts.settings.registration.redirect.link'));
                }
            }

            return $response->withRedirect(flextype('router')->pathFor('accounts.registration'));
        }

        return $response->withRedirect(flextype('router')->pathFor('accounts.registration'));
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
        if (flextype('registry')->get('plugins.accounts.settings.profile.enabled') === false) {

            $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return flextype('twig')->render($response, $template_path);
        }

        $email = $args['email'];

        // Redirect to accounts index if profile not founded
        if (!Filesystem::has(PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {
            return $response->withRedirect(flextype('router')->pathFor('accounts.index'));
        }

        $profile = flextype('serializers')->yaml()->decode(Filesystem::read(PATH['project'] . '/accounts/' . $email . '/profile.yaml'));
        $profile['email'] = $email;

        Arrays::delete($profile, 'uuid');
        Arrays::delete($profile, 'password');
        Arrays::delete($profile, 'hashed_password');
        Arrays::delete($profile, 'hashed_password_reset');
        Arrays::delete($profile, 'roles');

        $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/profile.html';
        $plugin_template_path = 'plugins/accounts/templates/profile.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return flextype('twig')->render(
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
        if (flextype('registry')->get('plugins.accounts.settings.profile_edit.enabled') === false) {

            $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/no-access.html';
            $plugin_template_path = 'plugins/accounts/templates/no-access.html';
            $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

            return flextype('twig')->render($response, $template_path);
        }

        $email = $args['email'];

        // Redirect to accounts index if profile not founded
        if (!Filesystem::has(PATH['project'] . '/accounts/' . $email . '/profile.yaml')) {
            return $response->withRedirect(flextype('router')->pathFor('accounts.index'));
        }

        $profile = flextype('serializers')->yaml()->decode(Filesystem::read(PATH['project'] . '/accounts/' . $email . '/profile.yaml'));

        $theme_template_path  = 'themes/' . flextype('registry')->get('plugins.site.settings.theme') . '/templates/accounts/templates/profile-edit.html';
        $plugin_template_path = 'plugins/accounts/templates/profile-edit.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        $profile['email'] = $email;

        if ($email === flextype('acl')->getUserLoggedInEmail()) {
            Arrays::delete($profile, 'uuid');
            Arrays::delete($profile, 'hashed_password');
            Arrays::delete($profile, 'roles');

            return flextype('twig')->render($response, $template_path, ['profile' => $profile]);
        }

        return $response->withRedirect(flextype('router')->pathFor('accounts.profile', ['email' => flextype('acl')->getUserLoggedInEmail()]));
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
            Arrays::delete($post_data, 'csrf_name');
            Arrays::delete($post_data, 'csrf_value');
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
            $user_file_data = flextype('serializers')->yaml()->decode($user_file_body);

            // Create admin account
            if (Filesystem::write(
                $user_profile_path,
                flextype('serializers')->yaml()->encode(
                    array_merge($user_file_data, $post_data)
                )
            )) {

                // Run event onAccountsProfileEdited
                flextype('emitter')->emit('onAccountsProfileEdited');

                // Add redirect to route name or to specific link
                if (flextype('registry')->get('plugins.accounts.settings.profile_edit.redirect.route')) {
                    if (flextype('registry')->get('plugins.accounts.settings.profile_edit.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.profile_edit.redirect.route.name'), ['email' => flextype('acl')->getUserLoggedInEmail()]));
                    }

                    if (flextype('registry')->get('plugins.accounts.settings.profile_edit.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.profile_edit.redirect.route.name'), ['email' => flextype('acl')->getUserLoggedInEmail()]));
                    }

                    return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.profile_edit.redirect.route.name')));
                } else {
                    return $response->withRedirect(flextype('registry')->get('plugins.accounts.settings.profile_edit.redirect.link'));
                }
            }

            return $response->withRedirect(flextype('router')->pathFor('accounts.registration'));
        }

        return $response->withRedirect(flextype('router')->pathFor('accounts.registration'));
    }

    /**
     * Logout page process
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     */
    public function logoutProcess(Request $request, Response $response) : Response
    {
        flextype('session')->destroy();

        // Run event onAccountsLogout
        flextype('emitter')->emit('onAccountsLogout');

        // Add redirect to route name or to specific link
        if (flextype('registry')->get('plugins.accounts.settings.logout.redirect.route')) {
            return $response->withRedirect(flextype('router')->pathFor(flextype('registry')->get('plugins.accounts.settings.logout.redirect.route.name')));
        } else {
            return $response->withRedirect(flextype('registry')->get('plugins.accounts.settings.logout.redirect.link'));
        }
    }
}
