<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype;

use Flextype\Component\Arr\Arr;
use Flextype\Component\Filesystem\Filesystem;
use Flextype\Component\Session\Session;
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

class AccountsController extends Container
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
        $accounts_list = Filesystem::listContents(PATH['project'] . '/accounts');
        $accounts      = [];

        foreach ($accounts_list as $account) {
            if ($account['type'] !== 'dir' || ! Filesystem::has($account['path'] . '/' . 'profile.yaml')) {
                continue;
            }

            $account = $this->serializer->decode(Filesystem::read($account['path'] . '/profile.yaml'), 'yaml');

            Arr::delete($account, 'hashed_password');
            Arr::delete($account, 'hashed_password_reset');

            $accounts[] = $account;
        }

        $theme_template_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/templates/index.html';
        $plugin_template_path = 'plugins/accounts/templates/index.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return $this->twig->render($response, $template_path, ['accounts' => $accounts]);
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
        if ($this->acl->isUserLoggedIn()) {
            //return $response->withRedirect($this->router->pathFor('accounts.profile', ['username' => $this->acl->getUserLoggedInUsername()]));
        }

        $theme_template_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/templates/login.html';
        $plugin_template_path = 'plugins/accounts/templates/login.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return $this->twig->render($response, $template_path);
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

        if (Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $post_data['username'] . '/profile.yaml')) {
            $user_file = $this->serializer->decode(Filesystem::read($_user_file), 'yaml', false);

            if (password_verify(trim($post_data['password']), $user_file['hashed_password'])) {
                Session::set('account_username', $user_file['username']);
                Session::set('account_roles', $user_file['roles']);
                Session::set('account_uuid', $user_file['uuid']);
                Session::set('account_is_user_logged_in', true);

                // Run event onAccountsUserLoggedIn
                $this->emitter->emit('onAccountsUserLoggedIn');

                // Add redirect to route name or to specific link
                if ($this->registry->get('plugins.accounts.settings.login.redirect.route')) {
                    if ($this->registry->get('plugins.accounts.settings.login.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.login.redirect.route.name'), ['username' => $user_file['username']]));
                    }

                    if ($this->registry->get('plugins.accounts.settings.login.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.login.redirect.route.name'), ['username' => $user_file['username']]));
                    }

                    return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.login.redirect.route.name')));
                } else {
                    return $response->withRedirect($this->registry->get('plugins.accounts.settings.login.redirect.link'));
                }
            }

            $this->flash->addMessage('error', __('accounts_message_wrong_username_password'));

            return $response->withRedirect($this->router->pathFor('accounts.login'));

        }

        $this->flash->addMessage('error', __('accounts_message_wrong_username_password'));

        return $response->withRedirect($this->router->pathFor('accounts.login'));
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
        $username = $args['username'];

        if (Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $username . '/profile.yaml')) {
            $user_file_body = Filesystem::read($_user_file);
            $user_file_data = $this->serializer->decode($user_file_body, 'yaml');

            if (password_verify(trim($args['hash']), $user_file_data['hashed_password_reset'])) {

                // Generate new passoword
                $raw_password    = bin2hex(random_bytes(16));
                $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

                $user_file_data['hashed_password'] = $hashed_password;

                Arr::delete($user_file_data, 'hashed_password_reset');

                if (Filesystem::write(
                    PATH['project'] . '/accounts/' . $username . '/profile.yaml',
                    $this->serializer->encode(
                        $user_file_data,
                        'yaml'
                    )
                )) {
                    // Instantiation and passing `true` enables exceptions
                    $mail = new PHPMailer(true);

                    $theme_new_password_email_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/emails/new-password.md';
                    $plugin_new_password_email_path = 'plugins/accounts/templates/emails/new-password.md';
                    $email_template_path            = Filesystem::has(PATH['project'] . '/' . $theme_new_password_email_path) ? $theme_new_password_email_path : $plugin_new_password_email_path;

                    $new_password_email = $this->serializer->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path), 'frontmatter');

                    //Recipients
                    $mail->setFrom($this->registry->get('plugins.accounts.settings.from.email'), $this->registry->get('plugins.accounts.settings.from.name'));
                    $mail->addAddress($user_file_data['email'], $username);

                    if ($this->registry->has('flextype.settings.url') && $this->registry->get('flextype.settings.url') !== '') {
                        $url = $this->registry->get('flextype.settings.url');
                    } else {
                        $url = Uri::createFromEnvironment(new Environment($_SERVER))->getBaseUrl();
                    }

                    $tags = [
                        '{sitename}' => $this->registry->get('plugins.site.settings.title'),
                        '{username}' => $username,
                        '{password}' => $raw_password,
                        '{url}' => $url,
                    ];

                    $subject = $this->parser->parse($new_password_email['subject'], 'shortcodes');
                    $content = $this->parser->parse($this->parser->parse($new_password_email['content'], 'shortcodes'), 'markdown');

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = strtr($subject, $tags);
                    $mail->Body    = strtr($content, $tags);

                    // Send email
                    $mail->send();

                    $this->flash->addMessage('success', __('accounts_message_new_password_was_sended'));

                    // Run event onAccountsNewPasswordSended
                    $this->emitter->emit('onAccountsNewPasswordSended');

                    // Add redirect to route name or to specific link
                    if ($this->registry->get('plugins.accounts.settings.new_password.redirect.route')) {
                        return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.new_password.redirect.route.name')));
                    } else {
                        return $response->withRedirect($this->registry->get('plugins.accounts.settings.new_password.redirect.link'));
                    }
                }
                return $response->withRedirect($this->router->pathFor('accounts.login'));
            }
            return $response->withRedirect($this->router->pathFor('accounts.login'));
        }

        return $response->withRedirect($this->router->pathFor('accounts.login'));
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
        $theme_template_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/templates/registration.html';
        $plugin_template_path = 'plugins/accounts/templates/reset-password.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return $this->twig->render($response, $template_path);
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

        // Get username
        $username = $post_data['username'];

        if (Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $username . '/profile.yaml')) {
            Arr::delete($post_data, 'csrf_name');
            Arr::delete($post_data, 'csrf_value');
            Arr::delete($post_data, 'form-save-action');
            Arr::delete($post_data, 'username');

            $raw_hash                           = bin2hex(random_bytes(16));
            $post_data['hashed_password_reset'] = password_hash($raw_hash, PASSWORD_BCRYPT);

            $user_file_body = Filesystem::read($_user_file);
            $user_file_data = $this->serializer->decode($user_file_body, 'yaml');

            // Create account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $username . '/profile.yaml',
                $this->serializer->encode(
                    array_merge($user_file_data, $post_data),
                    'yaml'
                )
            )) {
                // Instantiation and passing `true` enables exceptions
                $mail = new PHPMailer(true);

                $theme_reset_password_email_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/emails/reset-password.md';
                $plugin_reset_password_email_path = 'plugins/accounts/templates/emails/reset-password.md';
                $email_template_path              = Filesystem::has(PATH['project'] . '/' . $theme_reset_password_email_path) ? $theme_reset_password_email_path : $plugin_reset_password_email_path;

                $reset_password_email = $this->serializer->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path), 'frontmatter');

                //Recipients
                $mail->setFrom($this->registry->get('plugins.accounts.settings.from.email'), $this->registry->get('plugins.accounts.settings.from.name'));
                $mail->addAddress($user_file_data['email'], $username);

                if ($this->registry->has('flextype.settings.url') && $this->registry->get('flextype.settings.url') !== '') {
                    $url = $this->registry->get('flextype.settings.url');
                } else {
                    $url = Uri::createFromEnvironment(new Environment($_SERVER))->getBaseUrl();
                }

                $tags = [
                    '{sitename}' => $this->registry->get('plugins.site.settings.title'),
                    '{username}' => $username,
                    '{url}' => $url,
                    '{new_hash}' => $raw_hash,
                ];

                $subject = $this->parser->parse($reset_password_email['subject'], 'shortcodes');
                $content = $this->parser->parse($this->parser->parse($reset_password_email['content'], 'shortcodes'), 'markdown');

                // Content
                $mail->isHTML(true);
                $mail->Subject = strtr($subject, $tags);
                $mail->Body    = strtr($content, $tags);

                // Send email
                $mail->send();

                // Run event onAccountsPasswordReset
                $this->emitter->emit('onAccountsPasswordReset');

                // Add redirect to route name or to specific link
                if ($this->registry->get('plugins.accounts.settings.reset_password.redirect.route')) {
                    return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.reset_password.redirect.route.name')));
                } else {
                    return $response->withRedirect($this->registry->get('plugins.accounts.settings.reset_password.redirect.link'));
                }
            }

            return $response->withRedirect($this->router->pathFor('accounts.registration'));
        }

        return $response->withRedirect($this->router->pathFor('accounts.registration'));
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
        if ($this->registry->get('plugins.accounts.settings.registration.enabled') === false) {
             return $response->withRedirect($this->router->pathFor('accounts.login'));
        }

        if ($this->acl->isUserLoggedIn()) {
            return $response->withRedirect($this->router->pathFor('accounts.profile', ['username' => Session::get('account_username')]));
        }

        $theme_template_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/templates/registration.html';
        $plugin_template_path = 'plugins/accounts/templates/registration.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return $this->twig->render($response, $template_path);
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

        $username = $this->slugify->slugify($post_data['username']);

        if (! Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $username . '/profile.yaml')) {
            // Generate UUID
            $uuid = Uuid::uuid4()->toString();

            // Get time
            $time = date($this->registry->get('flextype.settings.date_format'), time());

            // Get username
            $username = $this->slugify->slugify($post_data['username']);

            // Get hashed password
            $hashed_password = password_hash($post_data['password'], PASSWORD_BCRYPT);

            $post_data['username']        = $username;
            $post_data['registered_at']   = $time;
            $post_data['uuid']            = $uuid;
            $post_data['hashed_password'] = $hashed_password;
            $post_data['roles']           = $this->registry->get('plugins.accounts.settings.registration.default_roles');
            $post_data['state']           = $this->registry->get('plugins.accounts.settings.registration.default_state');

            Arr::delete($post_data, 'csrf_name');
            Arr::delete($post_data, 'csrf_value');
            Arr::delete($post_data, 'password');
            Arr::delete($post_data, 'form-save-action');

            // Create accounts directory and account
            Filesystem::createDir(PATH['project'] . '/accounts/' . $this->slugify->slugify($post_data['username']));

            // Create admin account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $username . '/profile.yaml',
                $this->serializer->encode(
                    $post_data,
                    'yaml'
                )
            )) {
                // Instantiation and passing `true` enables exceptions
                $mail = new PHPMailer(true);

                $theme_new_user_email_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/emails/new-user.md';
                $plugin_new_user_email_path = 'plugins/accounts/templates/emails/new-user.md';
                $email_template_path        = Filesystem::has(PATH['project'] . '/' . $theme_new_user_email_path) ? $theme_new_user_email_path : $plugin_new_user_email_path;

                $new_user_email = $this->serializer->decode(Filesystem::read(PATH['project'] . '/' . $email_template_path), 'frontmatter');

                //Recipients
                $mail->setFrom($this->registry->get('plugins.accounts.settings.from.email'), $this->registry->get('plugins.accounts.settings.from.name'));
                $mail->addAddress($post_data['email'], $username);

                $tags = [
                    '{sitename}' => $this->registry->get('plugins.site.settings.title'),
                    '{username}' => $username,
                ];

                $subject = $this->parser->parse($new_user_email['subject'], 'shortcodes');
                $content = $this->parser->parse($this->parser->parse($new_user_email['content'], 'shortcodes'), 'markdown');

                // Content
                $mail->isHTML(true);
                $mail->Subject = strtr($subject, $tags);
                $mail->Body    = strtr($content, $tags);

                // Send email
                $mail->send();

                // Run event onAccountsNewUserRegistered
                $this->emitter->emit('onAccountsNewUserRegistered');

                // Add redirect to route name or to specific link
                if ($this->registry->get('plugins.accounts.settings.registration.redirect.route')) {
                    if ($this->registry->get('plugins.accounts.settings.registration.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.registration.redirect.route.name'), ['username' => $user_file['username']]));
                    }

                    if ($this->registry->get('plugins.accounts.settings.registration.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.registration.redirect.route.name'), ['username' => $user_file['username']]));
                    }

                    return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.registration.redirect.route.name')));
                } else {
                    return $response->withRedirect($this->registry->get('plugins.accounts.settings.registration.redirect.link'));
                }
            }

            return $response->withRedirect($this->router->pathFor('accounts.registration'));
        }

        return $response->withRedirect($this->router->pathFor('accounts.registration'));
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
        // Redirect to accounts index if profile not founded
        if (!Filesystem::has(PATH['project'] . '/accounts/' . $args['username'] . '/profile.yaml')) {
            return $response->withRedirect($this->router->pathFor('accounts.index'));
        }

        $profile = $this->serializer->decode(Filesystem::read(PATH['project'] . '/accounts/' . $args['username'] . '/profile.yaml'), 'yaml');

        Arr::delete($profile, 'uuid');
        Arr::delete($profile, 'hashed_password');
        Arr::delete($profile, 'hashed_password_reset');
        Arr::delete($profile, 'roles');

        $theme_template_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/templates/profile.html';
        $plugin_template_path = 'plugins/accounts/templates/profile.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        return $this->twig->render(
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
        // Redirect to accounts index if profile not founded
        if (!Filesystem::has(PATH['project'] . '/accounts/' . $args['username'] . '/profile.yaml')) {
            return $response->withRedirect($this->router->pathFor('accounts.index'));
        }

        $profile = $this->serializer->decode(Filesystem::read(PATH['project'] . '/accounts/' . $args['username'] . '/profile.yaml'), 'yaml');

        $theme_template_path  = 'themes/' . $this->registry->get('plugins.site.settings.theme') . '/templates/accounts/templates/profile-edit.html';
        $plugin_template_path = 'plugins/accounts/templates/profile-edit.html';
        $template_path        = Filesystem::has(PATH['project'] . '/' . $theme_template_path) ? $theme_template_path : $plugin_template_path;

        if ($profile['username'] === Session::get('account_username')) {
            Arr::delete($profile, 'uuid');
            Arr::delete($profile, 'hashed_password');
            Arr::delete($profile, 'roles');

            return $this->twig->render($response, $template_path, ['profile' => $profile]);
        }

        return $response->withRedirect($this->router->pathFor('accounts.profile', ['username' => Session::get('account_username')]));
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

        // Get username
        $username = $args['username'];

        if (Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $username . '/profile.yaml')) {
            Arr::delete($post_data, 'csrf_name');
            Arr::delete($post_data, 'csrf_value');
            Arr::delete($post_data, 'form-save-action');
            Arr::delete($post_data, 'password');
            Arr::delete($post_data, 'username');

            if (! empty($post_data['new_password'])) {
                $post_data['hashed_password'] = password_hash($post_data['new_password'], PASSWORD_BCRYPT);
                Arr::delete($post_data, 'new_password');
            } else {
                Arr::delete($post_data, 'password');
                Arr::delete($post_data, 'new_password');
            }

            $user_file_body = Filesystem::read($_user_file);
            $user_file_data = $this->serializer->decode($user_file_body, 'yaml');

            // Create admin account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $username . '/profile.yaml',
                $this->serializer->encode(
                    array_merge($user_file_data, $post_data),
                    'yaml'
                )
            )) {

                // Run event onAccountsProfileEdited
                $this->emitter->emit('onAccountsProfileEdited');

                // Add redirect to route name or to specific link
                if ($this->registry->get('plugins.accounts.settings.profile_edit.redirect.route')) {
                    if ($this->registry->get('plugins.accounts.settings.profile_edit.redirect.route.name') == 'accounts.profile') {
                        return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.profile_edit.redirect.route.name'), ['username' => $this->acl->getUserLoggedInUsername()]));
                    }

                    if ($this->registry->get('plugins.accounts.settings.profile_edit.redirect.route.name') == 'accounts.profileEdit') {
                        return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.profile_edit.redirect.route.name'), ['username' => $this->acl->getUserLoggedInUsername()]));
                    }

                    return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.profile_edit.redirect.route.name')));
                } else {
                    return $response->withRedirect($this->registry->get('plugins.accounts.settings.profile_edit.redirect.link'));
                }
            }

            return $response->withRedirect($this->router->pathFor('accounts.registration'));
        }

        return $response->withRedirect($this->router->pathFor('accounts.registration'));
    }

    /**
     * Logout page process
     *
     * @param Request  $request  PSR7 request
     * @param Response $response PSR7 response
     */
    public function logoutProcess(Request $request, Response $response) : Response
    {
        Session::destroy();

        // Run event onAccountsLogout
        $this->emitter->emit('onAccountsLogout');

        // Add redirect to route name or to specific link
        if ($this->registry->get('plugins.accounts.settings.logout.redirect.route')) {
            return $response->withRedirect($this->router->pathFor($this->registry->get('plugins.accounts.settings.logout.redirect.route.name')));
        } else {
            return $response->withRedirect($this->registry->get('plugins.accounts.settings.logout.redirect.link'));
        }
    }
}
