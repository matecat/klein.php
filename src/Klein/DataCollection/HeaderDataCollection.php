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

namespace Klein\DataCollection;

/**
 * HeaderDataCollection
 *
 * A DataCollection for HTTP headers
 */
class HeaderDataCollection extends DataCollection {

    /**
     * Constants
     */

    /**
     * Normalization option
     *
     * Don't normalize
     *
     * @type int
     */
    const int NORMALIZE_NONE = 0;

    /**
     * Normalization option
     *
     * Normalize the outer whitespace of the header
     *
     * @type int
     */
    const int NORMALIZE_TRIM = 1;

    /**
     * Normalization option
     *
     * Normalize the delimiters of the header
     *
     * @type int
     */
    const int NORMALIZE_DELIMITERS = 2;

    /**
     * Normalization option
     *
     * Normalize the case of the header
     *
     * @type int
     */
    const int NORMALIZE_CASE = 4;

    /**
     * Normalization option
     *
     * Normalize the header into canonical format
     *
     * @type int
     */
    const int NORMALIZE_CANONICAL = 8;

    /**
     * Normalization option
     *
     * Normalize using all normalization techniques
     *
     * @type int
     */
    const int NORMALIZE_ALL = -1;


    /**
     * Properties
     */

    /**
     * The header key normalization technique/style to
     * use when accessing headers in the collection
     *
     * @type int
     */
    protected int $normalization = self::NORMALIZE_ALL;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @override (doesn't call our parent)
     * @param array $headers       The headers of this collection
     * @param int   $normalization The header key normalization technique/style to use
     */
    public function __construct( array $headers = [], int $normalization = self::NORMALIZE_ALL ) {
        parent::__construct();
        $this->normalization = $normalization;

        foreach ( $headers as $key => $value ) {
            $this->set( $key, $value );
        }
    }

    /**
     * Get the header key normalization technique/style to use
     *
     * @return int
     */
    public function getNormalization(): int {
        return $this->normalization;
    }

    /**
     * Set the header key normalization technique/style to use
     *
     * @param int $normalization
     *
     * @return HeaderDataCollection
     */
    public function setNormalization( int $normalization ): static {
        $this->normalization = $normalization;

        return $this;
    }

    /**
     * Get a header
     *
     * {@inheritdoc}
     *
     * @param string     $key         The key of the header to return
     * @param mixed|null $default_val The default value of the header if it contains no value
     *
     * @return mixed
     * @see DataCollection::get()
     */
    public function get( string $key, mixed $default_val = null ): mixed {
        $key = $this->normalizeKey( $key );

        return parent::get( $key, $default_val );
    }

    /**
     * Set a header
     *
     * {@inheritdoc}
     *
     * @param string $key   The key of the header to set
     * @param mixed  $value The value of the header to set
     *
     * @return HeaderDataCollection
     * @see DataCollection::set()
     */
    public function set( string $key, mixed $value ): static {
        $key = $this->normalizeKey( $key );

        return parent::set( $key, $value );
    }

    /**
     * Check if a header exists
     *
     * {@inheritdoc}
     *
     * @param string $key The key of the header
     *
     * @return boolean
     * @see DataCollection::exists()
     */
    public function exists( string $key ): bool {
        $key = $this->normalizeKey( $key );

        return parent::exists( $key );
    }

    /**
     * Remove a header
     *
     * {@inheritdoc}
     *
     * @param string $key The key of the header
     *
     * @return void
     * @see DataCollection::remove()
     */
    public function remove( string $key ): void {
        $key = $this->normalizeKey( $key );

        parent::remove( $key );
    }

    /**
     * Normalize a header key based on our set normalization style
     *
     * @param string $key The ("field") key of the header
     *
     * @return string
     */
    protected function normalizeKey( string $key ): string {
        if ( $this->normalization & static::NORMALIZE_TRIM ) {
            $key = trim( $key );
        }

        if ( $this->normalization & static::NORMALIZE_DELIMITERS ) {
            $key = static::normalizeKeyDelimiters( $key );
        }

        if ( $this->normalization & static::NORMALIZE_CASE ) {
            $key = strtolower( $key );
        }

        if ( $this->normalization & static::NORMALIZE_CANONICAL ) {
            $key = static::canonicalizeKey( $key );
        }

        return $key;
    }

    /**
     * Normalize a header key's delimiters
     *
     * This will convert any space or underscore characters
     * to a more standard hyphen (-) character
     *
     * @param string $key The ("field") key of the header
     *
     * @return string
     */
    public static function normalizeKeyDelimiters( string $key ): string {
        return str_replace( [ ' ', '_' ], '-', $key );
    }

    /**
     * Canonicalize a header key
     *
     * The canonical format is all lower case except for
     * the first letter of "words" separated by a hyphen
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
     *
     * @param string $key The ("field") key of the header
     *
     * @return string
     */
    public static function canonicalizeKey( string $key ): string {
        $words = explode( '-', strtolower( $key ) );

        foreach ( $words as &$word ) {
            $word = ucfirst( $word );
        }

        return implode( '-', $words );
    }

}
