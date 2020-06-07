<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype;

use Slim\Http\Environment;
use Slim\Http\Uri;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function ltrim;
use Ramsey\Uuid\Uuid;
use Flextype\Component\Filesystem\Filesystem;
use Flextype\Component\Session\Session;
use Flextype\Component\Arr\Arr;

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
        // Get Query Params
        $query = $request->getQueryParams();

        $accounts_list = Filesystem::listContents(PATH['project'] . '/accounts');
        $accounts = [];

        foreach ($accounts_list as $account) {
            //Arr::delete($account, 'hashed_password');
            $accounts[] = $this->serializer->decode(Filesystem::read($account['path'] . '/profile.yaml'), 'yaml');
        }

        return $this->twig->render($response, 'plugins/accounts/templates/index.html', ['accounts' => $accounts]);
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
        // Get Query Params
        $query = $request->getQueryParams();

        if ($this->isUserLoggedIn()) {
            return $response->withRedirect($this->router->pathFor('accounts.profile', ['username' => Session::get('accounts_username')]));
        }

        return $this->twig->render($response, 'plugins/accounts/templates/login.html');
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
                Session::set('account_role', $user_file['role']);
                Session::set('account_uuid', $user_file['uuid']);
                Session::set('account_is_user_logged_in', true);

                return $response->withRedirect($this->router->pathFor('accounts.profile', ['username' => $user_file['username']]));
            }

            $this->flash->addMessage('error', __('admin_message_wrong_username_password'));

            return $response->withRedirect($this->router->pathFor('accounts.login'));
        }

        $this->flash->addMessage('error', __('admin_message_wrong_username_password'));

        return $response->withRedirect($this->router->pathFor('accounts.login'));
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
        // Get Query Params
        $query = $request->getQueryParams();

        if ($this->isUserLoggedIn()) {
            return $response->withRedirect($this->router->pathFor('accounts.profile'));
        }

        return $this->twig->render($response, 'plugins/accounts/templates/registration.html');
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

        if (! Filesystem::has($_user_file = PATH['project'] . '/accounts/' . $this->slugify->slugify($post_data['username']) . '/profile.yaml')) {

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
            $post_data['role']            = 'user';

            Arr::delete($post_data, 'csrf_name');
            Arr::delete($post_data, 'csrf_value');
            Arr::delete($post_data, 'password');
            Arr::delete($post_data, 'action');

            // Create accounts directory and account
            Filesystem::createDir(PATH['project'] . '/accounts/' . $this->slugify->slugify($post_data['username']));

            // Create admin account
            if (Filesystem::write(
                PATH['project'] . '/accounts/' . $username . '/profile.yaml',
                $this->serializer->encode(
                    $post_data
                , 'yaml')
            )) {
                return $response->withRedirect($this->router->pathFor('accounts.login'));
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
        // Get Query Params
        $query = $request->getQueryParams();

        $profile = $this->serializer->decode(Filesystem::read(PATH['project'] . '/accounts/' . $args['username'] . '/profile.yaml'), 'yaml');

        Arr::delete($profile, 'uuid');
        Arr::delete($profile, 'hashed_password');
        Arr::delete($profile, 'role');

        return $this->twig->render($response, 'plugins/accounts/templates/profile.html', ['profile' => $profile]);
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

        return $response->withRedirect($this->router->pathFor('accounts.login'));
    }

    public function isUserLoggedIn()
    {
        if (Session::exists('account_is_user_logged_in')) {
            return true;
        }

        return false;
    }
}
