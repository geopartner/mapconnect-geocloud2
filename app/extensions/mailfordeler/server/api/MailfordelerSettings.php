<?php
/**
 * @author     Rene Borella <rgb@geopartner.dk>
 * @copyright  2025 Geopartner A/S
 *
 */

namespace app\extensions\mailfordeler\api;

use app\api\v4\AbstractApi;
use app\api\v4\AcceptableAccepts;
use app\api\v4\AcceptableContentTypes;
use app\api\v4\AcceptableMethods;
use app\api\v4\ApiInterface;
use app\exceptions\GC2Exception;
use app\inc\Input;
use app\inc\Route2;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

#[AcceptableMethods(['GET', 'POST', 'HEAD', 'OPTIONS'])]
class MailfordelerSettings implements ApiInterface
{
    static function allow_db(): bool
    {
        // This function gets the current database from session, and rejects any subuser
        if (!App::$param["enableMailfordeler"][$_SESSION["screen_name"]] && empty(App::$param["enableMailfordeler"]["*"])) {
            return false;
        } else {
            return true;
        }
    }

    static function is_subuser(): bool
    {
        // This function gets the current database name from session, and rejects any subuser
        return $_SESSION["subuser"];
    }
    
    #[AcceptableAccepts(['application/json', '*/*'])]
    public function get_index(): array
    {
        // TODO: guard against subusers or users that are not allowed to use mailfordeler
        //if (self::is_subuser() || !self::allow_db()) {
        //    $code = "403";
        //    header("HTTP/1.0 $code " . Util::httpCodeText($code));
        //    die(Response::toJson(array(
        //        "success" => false,
        //        "message" => "Not allowed"
        //    )));
        //}

        // This function gets the current setup of the database in session.
        // TODO: Get settings from db

        return [
            'message' => $db
        ];
    }

    #[AcceptableContentTypes(['application/json'])]
    #[AcceptableAccepts(['application/json', '*/*'])]
    public function post_index(): array
    {
        // TODO: Implement post_index() method.
    }

    public function put_index(): array
    {
        // TODO: Implement put_index() method.
    }

    public function patch_index(): array
    {
        // TODO: Implement patch_index() method.
    }

    public function delete_index(): array
    {
        // TODO: Implement delete_index() method.
    }

    public function create_setup(): array
    {
        // This function creates a new mailfordeler setup based on the current session.
        // It should return an array with the setup details or an error message.
    
        $db = self::get_current_db();
    }

    /**
     * @throws GC2Exception
     */
    public function validate(): void
    {
        // TODO: Implement validate() method.
        if (false) {
            throw new GC2Exception('Something went wrong', 400, null, 'AN_ERROR_CODE');
        }

        // Use Symfony Validator to check the input
        if (Input::getMethod() == 'post') {
            $validator = Validation::createValidator();
            $collection = new Assert\Collection([
                'foo' => new Assert\Required([
                    new Assert\Type('string'),
                    new Assert\NotBlank(),
                ]),
                'bar' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\NotBlank(),
                    new Assert\Choice(['a', 'b', 'c']),
                ]),
            ]);
            $violations = $validator->validate(json_decode(Input::getBody(), true), $collection);
            AbstractApi::checkViolations($violations); // Throws exception if there are violations
        }
    }
}