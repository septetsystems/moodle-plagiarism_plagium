<?php
// This file is part of the Plagium Plugin for Moodle - https://www.plagium.com
//
// The Plagium Plugin for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// The Plagium Plugin for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with the Plagium Plugin for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains agreement class form.
 *
 * @package   plagiarism_plagium
 * @copyright 2023 Septet Systems
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagium\classes;
use curl;
use stored_file;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * plagium_api
 */
class plagium_api {
    /**
     * DOCUMENT
     */
    const DOCUMENT = "/document/create";

    /**
     * UPLOAD
     */
    const UPLOAD = "/document/upload";

    /**
     * RESULT
     */
    const RESULT = "/document/results";

    /**
     * API_DEFAULT_URL
     */
    const API_DEFAULT_URL = "https://api.plagium.com/300";

    /**
     * request
     *
     * @param  mixed $endpoint
     * @param  mixed $requesttype
     * @param  mixed $data
     * @param  mixed $filedata
     * @param  mixed $urlencodeddata
     * @return void
     */
    public function request($endpoint, $requesttype, $data, $filedata = null, $urlencodeddata = null) {
        $curl = new curl();
        $url = self::API_DEFAULT_URL . $endpoint;

        if ($urlencodeddata) {
            foreach ($data as $param => $value) {
                $url .= "&$param=" . urlencode($value);
            }
        }

        $response = null;
        if ($requesttype == "POST" && $filedata != null) {

            $boundary = uniqid();
            $delimiter = '-------------' . $boundary;
            $data = $this->build_data_file($boundary, $data, $filedata);

            $curl->setHeader(array(
                "Content-Type: multipart/form-data; boundary=" . $delimiter,
                "Content-Length: " . strlen($data)
            ));

            $response = $curl->post($url, $data);
        } else {
            $curl->setHeader('Content-Type:application/json');

            $payload = json_encode( $data );
            $response = $curl->post($url, $payload);
        }

        return json_decode($response);
    }

    /**
     * build_data_file
     *
     * @param  mixed $boundary
     * @param  mixed $fields
     * @param  mixed $file
     * @return void
     */
    private function build_data_file($boundary, $fields, $file) {
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content) {
            if (is_array($content)) {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                    . json_encode($content) . $eol;
            } else {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                    . $content . $eol;
            }
        }

        $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="file"; filename="' . $file->get_filename() . '"' . $eol
                . 'Content-Type: ' . $file->get_mimetype() . '' . $eol
                . 'Content-Transfer-Encoding: binary' . $eol;

        $data .= $eol;
        $data .= $file->get_content() . $eol;
        $data .= "--" . $delimiter . "--" . $eol;

        return $data;
    }
}
