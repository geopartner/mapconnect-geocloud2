<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2025 MapCentia ApS
 *
 */


use app\inc\Route2;
use app\extensions\mailfordeler\api\MailfordelerSettings;
use app\extensions\mailfordeler\api\MailfordelerHook;

// () = action, / = separator, {} = required, [] = optional

/*
GET /mailfordeler/settings

Gets the current setup of the database in session.
*/
Route2::add("/mailfordeler/settings/", new MailfordelerSettings());

/*
GET /mailfordeler/settings/create
Creates a new mailfordeler setup based on the current session.

*/
Route2::add("/mailfordeler/settings/(action)", new MailfordelerSettings());

/*
POST /mailfordeler/hook

Postmark webhook for events.
Currently supports the following events:
- [ ] Bounce
- [ ] Spam Complaint
- [ ] Delivery
- [ ] Open
- [ ] Click

*/
Route2::add("/mailfordeler/hook/", new MailfordelerHook());