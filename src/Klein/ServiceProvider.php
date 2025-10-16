<?php
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author          Chris O'Hara <cohara87@gmail.com>
 * @author          Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link            https://github.com/klein/klein.php
 * @license         MIT
 */

namespace Klein;

use Klein\DataCollection\DataCollection;

/**
 * ServiceProvider
 *
 * Service provider class for handling logic extending between
 * a request's data and a response's behavior
 */
class ServiceProvider
{

    /**
     * Class properties
     */

    /**
     * The Request instance containing HTTP request data and behaviors
     *
     * @type ?Request
     */
    protected ?Request $request = null;

    /**
     * The Response instance containing HTTP response data and behaviors
     *
     * @type ?AbstractResponse
     */
    protected ?AbstractResponse $response = null;

    /**
     * The id of the current PHP session
     *
     * @type string|boolean
     */
    protected string|bool $session_id = false;

    /**
     * The view layout
     *
     * @type ?string
     */
    protected ?string $layout = null;

    /**
     * The view to render
     *
     * @type ?string
     */
    protected ?string $view = null;

    /**
     * Shared data collection
     *
     * @type DataCollection
     */
    protected DataCollection $shared_data;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param Request|null $request Object containing all HTTP request data and behaviors
     * @param AbstractResponse|null $response Object containing all HTTP response data and behaviors
     */
    public function __construct(Request $request = null, AbstractResponse $response = null)
    {
        // Bind our objects
        $this->bind($request, $response);

        // Instantiate our shared data collection
        $this->shared_data = new DataCollection();
    }

    /**
     * Bind object instances to this service
     *
     * @param Request|null $request Object containing all HTTP request data and behaviors
     * @param AbstractResponse|null $response Object containing all HTTP response data and behaviors
     *
     * @return ServiceProvider
     */
    public function bind(Request $request = null, AbstractResponse $response = null): static
    {
        // Keep references
        $this->request = $request ?: $this->request;
        $this->response = $response ?: $this->response;

        return $this;
    }

    /**
     * Returns the shared data collection object
     *
     * @return DataCollection
     */
    public function sharedData(): DataCollection
    {
        return $this->shared_data;
    }

    /**
     * Get the current session's ID
     *
     * This will start a session if the current session id is null
     *
     * @return string|false
     */
    public function startSession(): bool|string
    {
        if (session_id() === '') {
            // Attempt to start a session
            session_start();

            $this->session_id = session_id() ?: false;
        }

        return $this->session_id;
    }

    /**
     * Stores a flash message of $type
     *
     * @param string $msg The message to flash
     * @param ?string $type The flash message type
     * @param array $params Optional params to be parsed by Markdown
     *
     * @return void
     */
    public function flash(string $msg, ?string $type = 'info', array $params = []): void
    {
        $this->startSession();
        if (!isset($_SESSION['__flashes'])) {
            $_SESSION['__flashes'] = [$type => []];
        } elseif (!isset($_SESSION['__flashes'][$type])) {
            $_SESSION['__flashes'][$type] = [];
        }
        $_SESSION['__flashes'][$type][] = $this->markdown($msg, $params);
    }


    /**
     * Flash an informational message with optional parameters
     *
     * @param string $msg The message to be flashed
     * @param array $params Optional associative array of parameters for the message
     *
     * @return void
     */
    public function flashInfo(string $msg, array $params = []): void
    {
        $this->flash($msg, 'info', $params);
    }

    /**
     * Returns and clears all flashes of the optional $type
     *
     * @param string|null $type The name of the flash message type
     *
     * @return array
     */
    public function flashes(?string $type = null): array
    {
        $this->startSession();

        if (!isset($_SESSION['__flashes'])) {
            return [];
        }

        if (null === $type) {
            $flashes = $_SESSION['__flashes'];
            unset($_SESSION['__flashes']);
        } else {
            $flashes = [];
            if (isset($_SESSION['__flashes'][$type])) {
                $flashes = $_SESSION['__flashes'][$type];
                unset($_SESSION['__flashes'][$type]);
            }
        }

        return $flashes;
    }

    /**
     * Render a text string as Markdown
     *
     * Supports basic Markdown syntax
     *
     * Also, this method takes in EITHER an array of optional arguments (as the second parameter)
     * ... OR this method will simply take a variable number of arguments (after the initial str arg)
     *
     * @param string $str The text strings to parse
     * @param array $args Optional arguments to be parsed by Markdown
     *
     * @return string
     */
    public static function markdown(string $str, array $args = []): string
    {
        // Create our Markdown parse/conversion regex's
        /** @noinspection HtmlUnknownTarget */
        $md = [
            '/\[([^\]]++)\]\(([^\)]++)\)/' => '<a href="$2">$1</a>',
            '/\*\*([^\*]++)\*\*/' => '<strong>$1</strong>',
            '/\*([^\*]++)\*/' => '<em>$1</em>'
        ];

        // Encode our args so we can insert them into an HTML string
        foreach ($args as &$arg) {
            $arg = htmlentities($arg ?? '', ENT_QUOTES, 'UTF-8');
        }

        // Actually make our Markdown conversion
        return vsprintf(preg_replace(array_keys($md), $md, $str), $args);
    }

    /**
     * Escapes a string for UTF-8 HTML displaying
     *
     * This is a quick macro for escaping strings designed
     * to be shown in a UTF-8 HTML environment. Its options
     * are otherwise limited by design
     *
     * @param string $str The string to escape
     * @param int $flags A bitmask of `htmlentities()` compatible flags
     *
     * @return string
     */
    public static function escape(string $str, int $flags = ENT_QUOTES): string
    {
        return htmlentities($str, $flags, 'UTF-8');
    }

    /**
     * Redirects the request to the current URL
     *
     * @return ServiceProvider
     */
    public function refresh(): static
    {
        $this->response->redirect(
            $this->request->uri()
        );

        return $this;
    }

    /**
     * Redirects the request back to the referrer
     *
     * @return ServiceProvider
     */
    public function back(): static
    {
        $referer = $this->request->server()->get('HTTP_REFERER');

        if (null !== $referer) {
            $this->response->redirect($referer);
        } else {
            $this->refresh();
        }

        return $this;
    }

    /**
     * Get (or set) the view's layout
     *
     * Simply calling this method without any arguments returns the current layout.
     * Calling with an argument, however, sets the layout to what was provided by the argument.
     *
     * @param ?string $layout The layout of the view
     *
     * @return string|null|ServiceProvider
     */
    public function layout(?string $layout = null): string|null|static
    {
        if (null !== $layout) {
            $this->layout = $layout;

            return $this;
        }

        return $this->layout;
    }

    /**
     * Renders the current view
     *
     * @return void
     */
    public function yieldView(): void
    {
        require $this->view;
    }

    /**
     * Renders a view + optional layout
     *
     * @param string $view The view to render
     * @param array $data The data to render in the view
     *
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        $original_view = $this->view;

        if (!empty($data)) {
            $this->shared_data->merge($data);
        }

        $this->view = $view;

        if (null === $this->layout) {
            $this->yieldView();
        } else {
            require $this->layout;
        }

        if (false !== $this->response->chunked) {
            $this->response->chunk();
        }

        // restore state for parent render()
        $this->view = $original_view;
    }

    /**
     * Renders a view without a layout
     *
     * @param string $view The view to render
     * @param array $data The data to render in the view
     *
     * @return void
     */
    public function partial(string $view, array $data = []): void
    {
        $layout = $this->layout;
        $this->layout = null;
        $this->render($view, $data);
        $this->layout = $layout;
    }

    /**
     * Add a custom validator for our validation method
     *
     * @param string $method The name of the validator method
     * @param callable $callback The callback to perform on validation
     *
     * @return void
     */
    public function addValidator(string $method, callable $callback): void
    {
        Validator::addValidator($method, $callback);
    }

    /**
     * Start a validator chain for the specified string
     *
     * @param string|null $string $string The string to validate
     * @param string|null $err The custom exception message to throw
     *
     * @return Validator
     */
    public function validate(?string $string, ?string $err = null): Validator
    {
        return new Validator($string, $err);
    }

    /**
     * Start a validator chain for the specified parameter
     *
     * @param string|null $param The name of the parameter to validate
     * @param string|null $err The custom exception message to throw
     *
     * @return Validator
     */
    public function validateParam(?string $param, ?string $err = null): Validator
    {
        return $this->validate($this->request->param($param), $err);
    }


    /**
     * Magic "__isset" method
     *
     * Allows the ability to arbitrarily check the existence of shared data
     * from this instance while treating it as an instance property
     *
     * @param string $key The name of the shared data
     *
     * @return boolean
     */
    public function __isset(string $key)
    {
        return $this->shared_data->exists($key);
    }

    /**
     * Magic "__get" method
     *
     * Allows the ability to arbitrarily request shared data from this instance
     * while treating it as an instance property
     *
     * @param string $key The name of the shared data
     *
     * @return string
     */
    public function __get(string $key)
    {
        return $this->shared_data->get($key);
    }

    /**
     * Magic "__set" method
     *
     * Allows the ability to arbitrarily set shared data from this instance
     * while treating it as an instance property
     *
     * @param string $key The name of the shared data
     * @param mixed $value The value of the shared data
     *
     * @return void
     */
    public function __set(string $key, mixed $value)
    {
        $this->shared_data->set($key, $value);
    }

    /**
     * Magic "__unset" method
     *
     * Allows the ability to arbitrarily remove shared data from this instance
     * while treating it as an instance property
     *
     * @param string $key The name of the shared data
     *
     * @return void
     */
    public function __unset(string $key)
    {
        $this->shared_data->remove($key);
    }
}
