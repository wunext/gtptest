<?php

$money = new NumberFormatter("pt-br", NumberFormatter::CURRENCY);
define("KB", 1024);
define("MB", 1048576);
define("GB", 1073741824);
define("TB", 0);
function companyIsOpen($company, $holiday = false)
{
    $week = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
    $day = $week[date("w", strtotime(date("Y/m/d")))];
    $companyHours = json_decode($company->hours);
    if (empty($companyHours) || intval($companyHours[0]->{"switch_" . $day}) === 0 && intval($companyHours[1]->{"switch_" . $day}) === 0 && intval($companyHours[2]->{"switch_" . $day}) === 0) {
        $store_hours = new StoreHours();
        $store_hours->hour_i = !empty($companyHours) ? substr($companyHours[0]->{$day . "_i"}, 0, 5) : "";
        $store_hours->hour_u = !empty($companyHours) ? substr($companyHours[0]->{$day . "_u"}, 0, 5) : "";
        return $store_hours;
    }
    $day_arr = [];
    intval($companyHours[0]->{"switch_" . $day}) == 1 && array_push($day_arr, substr($companyHours[0]->{$day . "_i"}, 0, 5) . "-" . substr($companyHours[0]->{$day . "_u"}, 0, 5));
    intval($companyHours[1]->{"switch_" . $day}) == 1 && array_push($day_arr, substr($companyHours[1]->{$day . "_i"}, 0, 5) . "-" . substr($companyHours[1]->{$day . "_u"}, 0, 5));
    intval($companyHours[2]->{"switch_" . $day}) == 1 && array_push($day_arr, substr($companyHours[2]->{$day . "_i"}, 0, 5) . "-" . substr($companyHours[2]->{$day . "_u"}, 0, 5));
    $hours = [$day => $day_arr];
    if (empty($hours)) {
        return false;
    }
    if ($holiday || intval($company->closed) == 1) {
        $store_hours = new StoreHours();
        $store_hours->hour_i = !empty($companyHours) ? substr($companyHours[0]->{$day . "_i"}, 0, 5) : "";
        $store_hours->hour_u = !empty($companyHours) ? substr($companyHours[0]->{$day . "_u"}, 0, 5) : "";
        return $store_hours;
    }
    $store_hours = new StoreHours($hours);
    $store_hours->hour_i = [substr($companyHours[0]->{$day . "_i"}, 0, 5), substr($companyHours[1]->{$day . "_i"}, 0, 5), substr($companyHours[2]->{$day . "_i"}, 0, 5)];
    $store_hours->hour_u = [substr($companyHours[0]->{$day . "_u"}, 0, 5), substr($companyHours[1]->{$day . "_u"}, 0, 5), substr($companyHours[2]->{$day . "_u"}, 0, 5)];
    return $store_hours;
}
function randomAlpha($bytes, $file)
{
    $result_bytes = random_bytes($bytes);
    $result_bytes = bin2hex($result_bytes);
    $ext = !empty($file) ? substr($file, strripos($file, ".")) : NULL;
    $res = !empty($file) ? $result_bytes . $ext : $result_bytes;
    return $res;
}
function QRCodeGenerate($content, $size, $margin)
{
    require_once "phpqrcode/qrlib.php";
    ob_start();
    QRcode::png($content, false, QR_ECLEVEL_H, $size, $margin);
    $result_qr_content_in_png = ob_get_contents();
    ob_end_clean();
    header("Content-type: text/html");
    $result_qr_content_in_base64 = base64_encode($result_qr_content_in_png);
    return "<img id=\"img_qrcode\" src=\"data:image/jpeg;base64," . $result_qr_content_in_base64 . "\" data-base-img=\"" . $result_qr_content_in_base64 . "\"/>";
}
function getDistance($lat1, $lon1, $lat2, $lon2)
{
    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);
    $lon1 = deg2rad($lon1);
    $lon2 = deg2rad($lon2);
    $dist = 6371 * acos(cos($lat1) * cos($lat2) * cos($lon2 - $lon1) + sin($lat1) * sin($lat2));
    $dist = number_format($dist, 2, ".", "");
    return $dist;
}
function removeAccent($str)
{
    $result = preg_replace(["/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/", "/(ç)/", "/(Ç)/"], explode(" ", "a A e E i I o O u U n N c C"), $str);
    return $result;
}
function msgOrderWhatsapp($order)
{
    $money = new NumberFormatter("pt-br", NumberFormatter::CURRENCY);
    $user_client = $order->user_client_id != -1 ? DB::select("SELECT * FROM users_client WHERE id = ?", [$order->user_client_id]) : NULL;
    if (isset($user_client) && empty($user_client)) {
        $user_client = NULL;
    }
    $order_data = json_decode($order->order_data);
    $whats_msg = "*Pedido:* " . $order->code . "%0A%0A";
    if (isset($user_client) || isset($order_data->customer_name)) {
        $whats_msg .= "*Cliente:*%0A";
        $whats_msg .= "*Nome:* " . (isset($user_client) ? $user_client[0]->name : $order_data->customer_name) . "%0A";
        $whats_msg .= "*Telefone:* " . (isset($user_client) ? $user_client[0]->phone_number : $order_data->customer_phone_number) . "%0A%0A";
    }
    foreach ($order_data->cart as $key => $cart) {
        foreach ($cart->item as $item) {
            $whats_msg .= "*Nome:* " . $item->name . "%0A";
            if (!empty($item->description)) {
                $whats_msg .= "*Descrição:* " . $item->description . "%0A";
            }
        }
        $whats_msg .= "*Quantidade:* " . $cart->amount . "x, *Valor:* " . $money->format($cart->price) . "%0A%0A";
        if (isset($cart->size)) {
            $whats_msg .= "*Tamanho:* " . $cart->size[0]->name . "%0A%0A";
        }
        if (isset($cart->additionals)) {
            $additionals_split = [];
            $whats_msg .= "*Adicionais:*%0A%0A";
            foreach ($cart->additionals as $additional) {
                $amount = 1;
                if (isset($cart->additionalsAmount)) {
                    $i = 0;
                    while ($i < count($cart->additionalsAmount)) {
                        $data = explode("_", $cart->additionalsAmount[$i]);
                        if ($data[0] == $additional->id) {
                            $amount = $data[1];
                        } else {
                            $i++;
                        }
                    }
                }
                $data_additional = ["name" => $additional->name, "price" => $additional->price, "price_total" => $additional->price * $amount, "amount" => $amount];
                $additional_category = DB::select("SELECT * FROM item_additional_category WHERE id = ?", [$additional->additional_category_id]);
                if (isset($additionals_split[$additional->additional_category_id])) {
                    array_push($additionals_split[$additional->additional_category_id], $data_additional);
                } else {
                    $additionals_split[$additional->additional_category_id][0] = $data_additional;
                }
            }
            foreach ($additionals_split as $key => $additional) {
                $additional_category = DB::select("SELECT * FROM item_additional_category WHERE id = ?", [$key]);
                if (!empty($additional_category)) {
                    $whats_msg .= "*" . $additional_category[0]->name . "*%0A";
                    foreach ($additional as $addt) {
                        $whats_msg .= $addt["name"] . "%0A";
                        $whats_msg .= $addt["amount"] . "x *|* " . (0 < $addt["price_total"] ? $money->format($addt["price_total"]) : "Grátis") . "%0A%0A";
                    }
                }
            }
        }
        if (isset($cart->option)) {
            $whats_msg .= "*Sabores:*%0A";
            foreach ($cart->option as $option) {
                $whats_msg .= "*Nome:* " . $option->name . "%0A";
            }
        }
        if (isset($cart->note) && !empty($cart->note)) {
            $whats_msg .= "*Observação:*%0A";
            $whats_msg .= $cart->note . "%0A";
        }
        if ($key + 1 < count($order_data->cart)) {
            $whats_msg .= "--------------%0A";
        }
    }
    $whats_msg .= "%0A";
    if (intval($order->delivery_type) === 0) {
        $address = json_decode($order->delivery_address);
        $address_whats_msg = "*Endereço:*%0A";
        $address_whats_msg .= $address[0]->street . ", Nº " . $address[0]->number . "%0A";
        $address_whats_msg .= $address[0]->city . "%0A";
        $address_whats_msg .= $address[0]->district . "%0A";
        if (!empty($address[0]->lat) && !empty($address[0]->lng)) {
            $address_whats_msg .= "*Latitude:* " . $address[0]->lat . "%0A";
            $address_whats_msg .= "*Longitude:* " . $address[0]->lng . "%0A";
        }
        if (!empty($address[0]->reference)) {
            $address_whats_msg .= "*Ponto de Referência:* " . $address[0]->reference . "%0A";
        }
        $whats_msg .= "%0A";
    } else {
        if (intval($order->delivery_type) == 1) {
            $address_whats_msg = "*O cliente fará a retirada no local.*%0A%0A";
        } else {
            if (intval($order->delivery_type) == 2) {
                $address = json_decode($order->delivery_table);
                $address_whats_msg = "*Mesa:*%0A";
                $address_whats_msg .= "Cliente: " . $address[0]->name_client . "%0A";
                $address_whats_msg .= "Nº " . $address[0]->table_number_client . "%0A";
                $whats_msg .= "%0A";
            }
        }
    }
    $whats_msg .= "*Valores:*%0A";
    $whats_msg .= "*Pedido:* " . $money->format($order->price_order) . "%0A";
    if (intval($order->delivery_type) === 0) {
        $whats_msg .= "*Taxa de Entrega:* " . $money->format($order->price_delivery) . "%0A";
    }
    $whats_msg .= "*Total:* " . $money->format($order->price_total) . "%0A";
    if (isset($order_data->coin)) {
        $whats_msg .= "*Moedas Utilizadas:* " . $money->format($order_data->coin) . "%0A";
    }
    if (intval($order->exchanged) == 1) {
        $whats_msg .= "*Troco:* " . $money->format($order->price_exchanged - $order->price_total) . "%0A";
    }
    $whats_msg .= "%0A";
    $whats_msg .= "*Forma de Pagamento:* ";
    switch ($order->payment_method) {
        case 0:
            $whats_msg .= "Dinheiro%0A";
            break;
        case 1:
            $whats_msg .= (isset($order_data->card_name) ? $order_data->card_name : "Cartão") . "%0A";
            break;
        case 2:
            $whats_msg .= "Online%0A";
            break;
        default:
            $whats_msg .= "%0A";
            $whats_msg = isset($address_whats_msg) ? $whats_msg . $address_whats_msg : $whats_msg;
            $whats_msg = isset($client_whats_msg) ? $whats_msg . $client_whats_msg : $whats_msg;
            return $whats_msg;
    }
}
function msgDetailsItem($order)
{
    while (isset($order) && !empty($order)) {
        return false;
    }
    $order_data = json_decode($order->order_data);
    $money = new NumberFormatter("pt-br", NumberFormatter::CURRENCY);
    $company = DB::select("SELECT * FROM company WHERE id = ?", [$order->company_id]);
    $html = "\n            <div class=\"mb-2\">\n                <strong>Código de Referência: </strong><span>" . $order->code . "</span>\n            </div>\n            \n            <div class=\"mb-2\">\n                <div class=\"accordion\" id=\"accordionFlushExample\">\n                    <div class=\"accordion-item shadow-sm\" style=\"border-radius: 10px;\">\n                        <h2 class=\"accordion-header\" id=\"flush-headingOne\">\n                            <button class=\"accordion-button collapsed shadow-none\" style=\"border-radius: 10px;\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#flush-collapseOne_" . $order->id . "\" aria-expanded=\"false\" aria-controls=\"flush-collapseOne_" . $order->id . "\">\n                                <span class=\"material-icons text-warning\">fastfood</span><b>&nbsp&nbspDetalhes do Pedido</b>\n                            </button>\n                        </h2>\n                        <div id=\"flush-collapseOne_" . $order->id . "\" class=\"accordion-collapse collapse\" aria-labelledby=\"flush-headingOne\" data-bs-parent=\"#accordionFlushExample\">\n                            <div class=\"accordion-body\" id=\"accordionDetails\">\n                                <div class=\"d-flex flex-column\">";
    foreach ($order_data->cart as $key => $cart) {
        $randomId = randomalpha(4, NULL);
        foreach ($cart->item as $item) {
            $html .= "<span><b>Nome:</b> " . $item->name . "</span>";
            if (!empty($item->description)) {
                $html .= "<span><b>Descrição:</b> " . $item->description . "</span>";
            }
        }
        $html .= "<span><b>Quantidade:</b> " . $cart->amount . "x, <b>Valor:</b> " . $money->format($cart->price) . "</span>";
        if (isset($cart->size)) {
            $html .= "<span><b>Tamanho:</b> " . $cart->size[0]->name . "</span>";
        }
        if (isset($cart->note) && !empty($cart->note)) {
            $html .= "<span><b>Observação:</b> " . $cart->note . "</span>";
        }
        if (isset($cart->additionals)) {
            $html .= "\n                                                <div class=\"accordion mt-2\" id=\"accordionAdditionals_" . $key . "_" . $randomId . "\">\n                                                    <div class=\"accordion-item shadow-sm\" style=\"border-radius: 10px;\">\n                                                        <h2 class=\"accordion-header\" id=\"flush-headingOne\">\n                                                            <button class=\"accordion-button collapsed shadow-none\" style=\"border-radius: 10px;\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#flush-collapseAdditionals_" . $key . "_" . $randomId . "\" aria-expanded=\"false\">\n                                                                <b>Adicionais</b>\n                                                            </button>\n                                                        </h2>\n                                                        <div id=\"flush-collapseAdditionals_" . $key . "_" . $randomId . "\" class=\"accordion-collapse collapse\" aria-labelledby=\"flush-headingOne\" data-bs-parent=\"#accordionAdditionals_" . $key . "_" . $randomId . "\">\n                                                            <div class=\"accordion-body\" id=\"accordionAdditionals\">\n                                                                <div class=\"d-flex flex-column\">";
            foreach ($cart->additionals as $additional) {
                $amount = 1;
                if (isset($cart->additionalsAmount)) {
                    $i = 0;
                    while ($i < count($cart->additionalsAmount)) {
                        $data = explode("_", $cart->additionalsAmount[$i]);
                        if ($data[0] == $additional->id) {
                            $amount = $data[1];
                        } else {
                            $i++;
                        }
                    }
                }
                $data_additional = ["name" => $additional->name, "price" => $additional->price, "price_total" => $additional->price * $amount, "amount" => $amount];
                $additional_category = DB::select("SELECT * FROM item_additional_category WHERE id = ?", [$additional->additional_category_id]);
                if (isset($additionals_split[$additional->additional_category_id])) {
                    array_push($additionals_split[$additional->additional_category_id], $data_additional);
                } else {
                    $additionals_split[$additional->additional_category_id][0] = $data_additional;
                }
            }
            foreach ($additionals_split as $key => $additional) {
                $additional_category = DB::select("SELECT * FROM item_additional_category WHERE id = ?", [$key]);
                if (!empty($additional_category)) {
                    $html .= "<b>" . $additional_category[0]->name . "</b>";
                    foreach ($additional as $addt) {
                        $html .= $addt["name"];
                        $html .= "<span>" . $addt["amount"] . "x <b>|</b> " . (0 < $addt["price_total"] ? $money->format($addt["price_total"]) : "Grátis") . "</span><br>";
                    }
                }
            }
            $html .= "\n                                                                </div>\n                                                            </div>\n                                                        </div>\n                                                    </div>\n                                                </div>\n                                            ";
        }
        if (isset($cart->option)) {
            $html .= "\n                                                <div class=\"accordion mt-2\" id=\"accordionOption_" . $key . "_" . $randomId . "\">\n                                                    <div class=\"accordion-item shadow-sm\" style=\"border-radius: 10px;\">\n                                                        <h2 class=\"accordion-header\" id=\"flush-headingOne\">\n                                                            <button class=\"accordion-button collapsed shadow-none\" style=\"border-radius: 10px;\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#flush-collapseOption_" . $key . "_" . $randomId . "\" aria-expanded=\"false\">\n                                                                <b>Sabores</b>\n                                                            </button>\n                                                        </h2>\n                                                        <div id=\"flush-collapseOption_" . $key . "_" . $randomId . "\" class=\"accordion-collapse collapse\" aria-labelledby=\"flush-headingOne\" data-bs-parent=\"#accordionOption_" . $key . "_" . $randomId . "\">\n                                                            <div class=\"accordion-body\" id=\"accordionAdditional\">\n                                                                <div class=\"d-flex flex-column\">";
            foreach ($cart->option as $option) {
                $html .= "<span><b>Nome:</b> " . $option->name . "</span>";
            }
            $html .= "\n                                                                </div>\n                                                            </div>\n                                                        </div>\n                                                    </div>\n                                                </div>\n                                            ";
        }
        if ($key + 1 < count($order_data->cart)) {
            $html .= "<hr>";
        }
    }
    if (!empty($order_data->note)) {
        $html .= "\n                                            <div class=\"alert alert-info mt-2\">\n                                                <b>Obs: </b> " . $order_data->note . "\n                                            </div>\n                                        ";
    }
    $html .= "\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        ";
    $address = json_decode($order->delivery_address);
    $html .= "<div class=\"mb-2\">";
    if (intval($order->delivery_type) === 0) {
        $html .= "<strong>Forma de Entrega: </strong><span>Delivery</span>";
        $html .= "<div class=\"clearfix\"></div>";
        $html .= "\n                    <div class=\"mt-2 w-100 bg-white shadow-sm py-2 px-3\" style=\"border-radius: 7px;\">\n                        <div class=\"row\">\n                            <div class=\"col-auto d-flex align-items-center\">\n                                <span class=\"material-icons text-light-green\" style=\"font-size: 2.2rem; !important\">place</span>\n                            </div>\n                            <div class=\"col\">\n                                <span class=\"text-secondary\"><i>Endereço: </i></span><span>" . $address[0]->street . ", Nº " . $address[0]->number . "</span>\n                                <div class=\"clearfix\"></div>\n                                \n                                <span class=\"text-secondary\"><i>Cidade: </i></span><span>" . $address[0]->city . "</span>,\n                                <span class=\"text-secondary\"><i>Bairro: </i></span><span>" . $address[0]->district . "</span>";
        if (!empty($address[0]->lat) && !empty($address[0]->lng)) {
            $html .= "\n                                        <div class=\"clearfix\"></div>\n                                        <span class=\"text-secondary\"><i>Latitude: </i></span><span>" . $address[0]->lat . "</span>,\n                                        <span class=\"text-secondary\"><i>Longitude: </i></span><span>" . $address[0]->lng . "</span>\n                                    ";
        }
        if (!empty($address[0]->reference)) {
            $html .= "\n                                        <div class=\"clearfix\"></div>\n                                        <span class=\"text-secondary\"><i>Ponto de Referência: </i></span><span>" . $address[0]->reference . "</span>\n                                    ";
        }
        $html .= "\n                            </div>\n                        </div>\n                    </div>\n                ";
        $address_whats_msg = "*Endereço:*%0A";
        $address_whats_msg .= $address[0]->street . ", Nº " . $address[0]->number . "%0A";
        $address_whats_msg .= $address[0]->city . "%0A";
        $address_whats_msg .= $address[0]->district . "%0A";
        if (!empty($address[0]->lat) && !empty($address[0]->lng)) {
            $address_whats_msg .= "*Latitude:* " . $address[0]->lat . "%0A";
            $address_whats_msg .= "*Longitude:* " . $address[0]->lng . "%0A";
        }
        if (!empty($address[0]->reference)) {
            $address_whats_msg .= "*Ponto de Referência:* " . $address[0]->reference;
        }
    } else {
        if ($order->delivery_type == 1) {
            $html .= "<strong>Forma de Entrega: </strong><span>O cliente irá fazer a retirada no estabelecimento!</span>";
        } else {
            if ($order->delivery_type == 2) {
                $table = json_decode($order->delivery_table);
                $html .= "<strong>Forma de Entrega: </strong><span>Na Mesa</span>";
                $html .= "<div class=\"clearfix\"></div>";
                $html .= "\n                    <div class=\"mt-2 w-100 bg-white shadow-sm py-2 px-3\" style=\"border-radius: 7px;\">\n                        <div class=\"row\">\n                            <div class=\"col-auto d-flex align-items-center\">\n                                <span class=\"material-icons text-light-green\" style=\"font-size: 2.2rem; !important\">table_restaurant</span>\n                            </div>\n                            <div class=\"col\">\n                                <span class=\"text-secondary\"><i>Cliente: </i></span><span>" . (isset($table[0]->name_client) ? $table[0]->name_client : "") . "</span>\n                                <div class=\"clearfix\"></div>\n                                \n                                <span class=\"text-secondary\"><i>Nº da Mesa: </i></span><span>" . (isset($table[0]->table_number_client) ? $table[0]->table_number_client : "") . "</span>\n                            </div>\n                        </div>\n                    </div>\n                ";
            }
        }
    }
    $html .= "</div>";
    $html .= "\n            <div class=\"mb-2\">\n                <div class=\"mt-1 w-100 bg-white shadow-sm py-2 px-3\" style=\"border-radius: 7px;\">\n                    <div class=\"row\">\n                        <div class=\"col-auto d-flex align-items-center\">\n                            <span class=\"material-icons text-light-green\" style=\"font-size: 2.2rem; !important\">attach_money</span>\n                        </div>\n                        <div class=\"col\">\n                            <span class=\"text-secondary\"><i>Valor do Pedido: </i></span><span>" . $money->format($order->price_order) . "</span>\n                            <div class=\"clearfix\"></div>";
    if (intval($order->delivery_type) === 0) {
        $html .= "<span class=\"text-secondary\"><i>Taxa da Entrega: </i></span><span>" . $money->format($order->price_delivery) . "</span>";
        $html .= "<div class=\"clearfix\"></div>";
    }
    $html .= "<span class=\"text-secondary\"><i>Valor Total: </i></span><span>" . $money->format($order->price_total) . "</span>";
    if (isset($order_data->coin)) {
        $html .= "<div class=\"clearfix\"></div>";
        $html .= "<span class=\"text-secondary\"><i>Moedas Utilizadas: </i></span><span>" . $money->format($order_data->coin) . "</span>";
    }
    if ($order->exchanged == 1) {
        $html .= "<div class=\"clearfix\"></div>";
        $html .= "<span class=\"text-secondary\"><i>Troco: </i></span><span>" . $money->format($order->price_exchanged - $order->price_total) . "</span>";
    }
    $html .= "\n                        </div>\n                    </div>\n                </div>\n            </div>\n        ";
    $user_client = $order->user_client_id != -1 ? DB::select("SELECT * FROM users_client WHERE id = ?", [$order->user_client_id]) : NULL;
    if (isset($user_client) && empty($user_client)) {
        $user_client = NULL;
    }
    if (isset($user_client) && !empty($user_client) || isset($order_data->customer_name)) {
        $html .= "\n                <div class=\"mb-2\">\n                    <div class=\"mt-1 w-100 bg-white shadow-sm py-2 px-3\" style=\"border-radius: 7px;\">\n                        <div class=\"row\">\n                            <div class=\"col-auto d-flex align-items-center\">\n                                <span class=\"material-icons text-light-green\" style=\"font-size: 2.2rem; !important\">person</span>\n                            </div>\n                            <div class=\"col\">\n                                <span class=\"text-secondary\"><i>Cliente: </i></span><span>" . (isset($user_client) && !empty($user_client) ? $user_client[0]->name : $order_data->customer_name) . "</span>\n                                <div class=\"clearfix\"></div>                                        \n                                <span class=\"text-secondary\"><i>Telefone: </i></span><span>" . (isset($user_client) && !empty($user_client) ? $user_client[0]->phone_number : $order_data->customer_phone_number) . "</span>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            ";
        $client_whats_msg = "*Cliente:*%0A";
        $client_whats_msg .= (isset($user_client) && !empty($user_client) ? $user_client[0]->name : $order_data->customer_name) . "%0A";
        $client_whats_msg .= (isset($user_client) && !empty($user_client) ? $user_client[0]->phone_number : $order_data->customer_phone_number) . "%0A";
    }
    if (intval($order->is_schedule_order) == 1) {
        $html .= "\n                <div class=\"mb-2\">\n                    <div class=\"mt-1 w-100 bg-white shadow-sm py-2 px-3\" style=\"border-radius: 7px;\">\n                        <div class=\"row\">\n                            <div class=\"col-auto d-flex align-items-center\">\n                                <span class=\"material-icons text-light-green\" style=\"font-size: 2.2rem; !important\">schedule</span>\n                            </div>\n                            <div class=\"col d-flex align-items-center\">\n                                <span class=\"text-secondary\"><i>Agendado para às: </i></span><span>&nbsp" . date("H:i", strtotime($order->hour_schedule_order)) . "</span>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            ";
    }
    $html .= "\n            <div class=\"mb-2\">\n                <strong>Forma de Pagamento: </strong>";
    switch ($order->payment_method) {
        case 0:
            $html .= "Dinheiro";
            break;
        case 1:
            $html .= isset($order_data->card_name) ? $order_data->card_name : "Cartão";
            break;
        case 2:
            $html .= "Online";
            break;
        default:
            $html .= "\n            </div>\n        ";
            if (intval($order->delivery_type) === 0 && isset($company)) {
                $deliveries = DB::select("SELECT * FROM deliveries WHERE order_id = ? AND company_id = ?", [$order->id, $company[0]->id]);
                $html .= "<div class=\"mb-2\">";
                if (empty($deliveries)) {
                    $html .= "\n                        <div class=\"accordion\" id=\"accordionDeliveryman\">\n                            <div class=\"accordion-item shadow-sm\" style=\"border-radius: 10px;\">\n                                <h2 class=\"accordion-header\" id=\"flush-headingOne\">\n                                    <button class=\"accordion-button collapsed shadow-none\" style=\"border-radius: 10px;\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#flush-collapseDeliveryman\" aria-expanded=\"false\" aria-controls=\"flush-collapseDeliveryman\">\n                                        <span class=\"material-icons text-warning\">delivery_dining</span><b>&nbsp&nbspEntregadores</b>\n                                    </button>\n                                </h2>\n                                <div id=\"flush-collapseDeliveryman\" class=\"accordion-collapse collapse\" aria-labelledby=\"flush-headingOne\" data-bs-parent=\"#accordionDeliveryman\">\n                                    <div class=\"accordion-body\" id=\"accordionDeliverymanBody\">";
                    $deliverymans = DB::select("SELECT * FROM deliveryman WHERE company_id = ?", [$company[0]->id]);
                    if (!empty($deliverymans)) {
                        $html .= "<div class=\"overflow-hidden\">";
                        foreach ($deliverymans as $deliveryman) {
                            $dataDeliveryman = DB::select("SELECT * FROM users_deliveryman WHERE id = ?", [$deliveryman->user_deliveryman_id]);
                            if (!empty($dataDeliveryman)) {
                                $html .= "\n                                                            <div class=\"row\">\n                                                                <div class=\"col pe-0\">\n                                                                    <div class=\"bg-white border d-flex flex-column p-2 ps-3\" style=\"border-radius: 10px 0px 0px 10px;\">\n                                                                        <span id=\"strongAdditionalCategory_" . $deliveryman->id . "\">" . $dataDeliveryman[0]->name . "</span>\n                                                                    </div>\n                                                                </div>\n                                                                <div class=\"col-auto ps-0 d-flex\">\n                                                                    <div class=\"btn-light-green d-flex justify-content-center align-items-center h-100 px-3\" style=\"cursor: pointer; border-radius: 0px 10px 10px 0px;\" onclick=\"alertConfirmMessage('modalConfirm','Deseja realmente designar este entregador?',3,null,'selectDeliveryman(" . $order->id . "," . $dataDeliveryman[0]->id . ")');\">\n                                                                        <span class=\"material-icons text-white\" style=\"font-size: 20px; !important\">delivery_dining</span>\n                                                                    </div>\n                                                                </div>\n                                                            </div>\n                                                        ";
                            }
                        }
                        $html .= "</div>";
                    } else {
                        $html .= "\n                                                <div class=\"alert alert-info\">\n                                                    Você não possui nenhum entregador cadastrado!\n                                                </div>\n                                            ";
                    }
                    $html .= "\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    ";
                } else {
                    $dataDeliveryman = DB::select("SELECT * FROM users_deliveryman WHERE id = ?", [$deliveries[0]->user_deliveryman_id]);
                    if (!empty($dataDeliveryman)) {
                        $phone_number_deliveryman = $dataDeliveryman[0]->phone_number;
                        $phone_number_deliveryman = str_replace("(", "", $phone_number_deliveryman);
                        $phone_number_deliveryman = str_replace(")", "", $phone_number_deliveryman);
                        $phone_number_deliveryman = str_replace("-", "", $phone_number_deliveryman);
                        $phone_number_deliveryman = str_replace(" ", "", $phone_number_deliveryman);
                        $html .= "\n                            <div class=\"mt-1 w-100 bg-white shadow-sm py-2 px-3\" style=\"border-radius: 7px;\">\n                                <div class=\"row\">\n                                    <div class=\"col-auto d-flex align-items-center\">\n                                        <span class=\"material-icons text-light-green\" style=\"font-size: 2.2rem; !important\">delivery_dining</span>\n                                    </div>\n                                    <div class=\"col\">\n                                        <span class=\"text-secondary\"><i>Entregador: </i></span><span>" . $dataDeliveryman[0]->name . "</span>\n                                        <div class=\"clearfix\"></div>                                        \n                                        <span class=\"text-secondary\"><i>Telefone: </i></span><span>" . $dataDeliveryman[0]->phone_number . "</span>\n                                    </div>\n                                    <div class=\"col-auto d-flex justify-content-center align-items-center\">\n                                        <button class=\"btn-light-green border-0 rounded-circle d-flex align-items-center justify-content-center me-1\" style=\"width: 26px; height: 26px\" onclick=\"window.open('https://api.whatsapp.com/send?phone=55" . $phone_number_deliveryman . "&text=" . msgorderwhatsapp($order) . "');\">\n                                            <i class=\"fab fa-whatsapp\"></i>\n                                        </button>\n                                        <button class=\"btn-light-red border-0 rounded-circle d-flex align-items-center justify-content-center\" style=\"width: 26px; height: 26px\" onclick=\"alertConfirmMessage('modalConfirm','Deseja realmente declinar este entregador?',3,null,'unselectDeliveryman(" . $deliveries[0]->id . ")');\">\n                                            <i class=\"fas fa-times\"></i>\n                                        </button>\n                                    </div>\n                                </div>\n                            </div>\n                        ";
                    }
                }
                $html .= "</div>";
            }
            return $html;
    }
}
function htmlDivPrint($order)
{
    if (isset($order) && !empty($order)) {
        $order_data = json_decode($order->order_data);
        $money = new NumberFormatter("pt-br", NumberFormatter::CURRENCY);
        $company = DB::select("SELECT * FROM company WHERE id = ?", [$order->company_id]);
        $client = DB::select("SELECT * FROM users_client WHERE id = ?", [$order->user_client_id]);
        $printer = DB::select("SELECT * FROM printer WHERE company_id = ?", [$company[0]->id]);
        $orders_print_settings = DB::select("SELECT * FROM orders_print_settings WHERE company_id = ?", [$company[0]->id]);
        $orders_print_settings = !empty($orders_print_settings) ? json_decode($orders_print_settings[0]->settings) : NULL;
        $document = json_decode($company[0]->document);
        $html = "\n            <div style=\"display: none;\">\n                <div id=\"printOrder_" . $order->id . "\" class=\"p-2\" style=\"width: 320px; background-color: #ffffff; font-family: " . (!empty($printer) ? $printer[0]->font_name : "Arial, Helvetica, sans-serif") . "; font-size: " . (!empty($printer) ? $printer[0]->font_size . "px" : "14px") . "; color: " . (!empty($printer) ? $printer[0]->font_color : "#000000") . "; -webkit-print-color-adjust: exact; color-adjust: exact;\">\n                    ";
        if (isset($orders_print_settings) && isset($orders_print_settings->logo) && intval($orders_print_settings->logo->switch) == 1 && !empty($orders_print_settings->logo->image)) {
            $html .= "\n                            <div class=\"mb-2 d-flex justify-content-center\">\n                                <img src=\"../../public/images/coupon/" . $orders_print_settings->logo->image . "\" style=\"width: 75px; height: 75px;\">\n                            </div>\n                        ";
        }
        $html .= "\n                    <div class=\"text-center d-flex flex-column\">\n                        <b>" . $order->code . "</b>\n                        <span>" . $company[0]->name . "</span>";
        if (isset($orders_print_settings) && isset($orders_print_settings->cnpj) && intval($orders_print_settings->cnpj) == 1 && !empty($document) && isset($document->cnpj) && !empty($document->cnpj)) {
            $html .= "<span>" . $document->cnpj . "</span>";
        }
        $html .= "\n                    </div>\n                    \n                    <div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\"><p>----------------------------------------------------------------------------------------</p></div>\n                    \n                    <p>Data: " . date("d/m/Y", strtotime($order->date_created)) . " às " . date("H:i", strtotime($order->date_created)) . "</p>";
        if ((!empty($client) || !empty($order_data->customer_name)) && (!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_client) == 1)) {
            $html .= "\n                                <div class=\"d-flex flex-column\">\n                                    <b>Detalhes do Comprador:</b>\n                                    <span>Nome: " . (!empty($client) ? $client[0]->name : $order_data->customer_name) . "</span>\n                                    <span>Telefone: " . (!empty($client) ? $client[0]->phone_number : $order_data->customer_phone_number) . "</span>\n                                </div>\n                                " . (empty($printer) || intval($printer[0]->spacing_type) === 0 ? "<div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\"><p>----------------------------------------------------------------------------------------</p></div>" : "<p>&nbsp;</p>") . "\n                            ";
        }
        if (!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_delivery) == 1) {
            $html .= "<div class=\"d-flex flex-column\">";
            if (intval($order->delivery_type) === 0) {
                $address = json_decode($order->delivery_address);
                $html .= "\n                                    <span><b>Entrega no endereço:</b></span>\n                                    <span>" . $address[0]->street . ", nº " . $address[0]->number . "</span>\n                                    <span>" . $address[0]->district . ", " . $address[0]->city . "</span>\n                                ";
                if (!empty($address[0]->reference)) {
                    $html .= "<span>" . $address[0]->reference . "</span>";
                }
                $html .= empty($printer) || intval($printer[0]->spacing_type) === 0 ? "<div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\"><p>----------------------------------------------------------------------------------------</p></div>" : "<p>&nbsp;</p>";
            } else {
                if ($order->delivery_type == 1) {
                    $html .= "<p><b>Tipo de delivery:</b> Retirada no local</p>";
                } else {
                    if ($order->delivery_type == 2) {
                        $table = json_decode($order->delivery_table);
                        $html .= "\n                                    <span><b>Entrega na mesa:</b></span>\n                                    <span>" . $table[0]->name_client . "</span>\n                                    <span>Nº " . $table[0]->table_number_client . "</span>\n                                ";
                        $html .= empty($printer) || intval($printer[0]->spacing_type) === 0 ? "<div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\"><p>----------------------------------------------------------------------------------------</p></div>" : "<p>&nbsp;</p>";
                    }
                }
            }
            $html .= "</div>";
        }
        if (!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_order) == 1) {
            $html .= "\n                            <div class=\"d-flex flex-column\">\n                                <p>*** Detalhes do Pedido: ***</p>\n                                <div class=\"d-flex flex-column\">";
            foreach ($order_data->cart as $key => $cart) {
                foreach ($cart->item as $item) {
                    $html .= "<b>" . $item->name . "</b>";
                }
                $html .= "<span>Qtd. " . $cart->amount . "x, Valor: " . $money->format($cart->price) . "</span>";
                if ((!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_order_item_size) == 1) && isset($cart->size)) {
                    $html .= "<span>Tamanho: " . $cart->size[0]->name . "</span>";
                }
                if ((!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_order_item_additional) == 1) && isset($cart->additionals)) {
                    $html .= "\n                                                    <span>Adicionais: </span>\n                                                    <div class=\"d-flex\">";
                    foreach ($cart->additionals as $pos => $additional) {
                        $additional_category = DB::select("SELECT * FROM item_additional_category WHERE id = ?", [$additional->additional_category_id]);
                        $html .= $additional->name . " | ";
                        foreach ($cart->additionalsAmount as $value) {
                            $additionalsAmount = explode("_", $value);
                            if (intval($additionalsAmount[0]) == $additional->id) {
                                $html .= $additionalsAmount[1] . "x";
                                if ($pos + 1 < count($cart->additionals)) {
                                    $html .= ", ";
                                }
                            }
                        }
                    }
                    $html .= "<p>&nbsp;</p>\n                                                    </div>\n                                                ";
                }
                if ((!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_order_item_flavor) == 1) && isset($cart->option)) {
                    $html .= "\n                                                    <span>Sabores: </span>\n                                                    <div class=\"d-flex flex-column\">";
                    foreach ($cart->option as $pos => $option) {
                        $html .= $option->name;
                        if ($pos + 1 < count($cart->option)) {
                            $html .= ", ";
                        }
                    }
                    $html .= "\n                                                    </div>\n                                                ";
                }
                if ((!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_order_item_note) == 1) && isset($cart->note) && !empty($cart->note)) {
                    $html .= "\n                                                    <b>Obs: </b>\n                                                    <div class=\"d-flex flex-column\">\n                                                        <span>" . $cart->note . "</span>\n                                                    </div>\n                                                ";
                }
                if ($key + 1 < count($order_data->cart)) {
                    $html .= empty($printer) || intval($printer[0]->spacing_type) === 0 ? "<div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\"><p>----------------------------------------------------------------------------------------</p></div>" : "<p>&nbsp;</p>";
                }
            }
            $html .= "\n                                </div>";
            if ((!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_order_note) == 1) && !empty($order_data->note)) {
                $html .= "\n                                            <p>&nbsp;</p>\n                                            <b>Observação Geral: </b> " . $order_data->note;
            }
            $html .= (empty($printer) || intval($printer[0]->spacing_type) === 0 ? "<div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\"><p>----------------------------------------------------------------------------------------</p></div>" : "<p>&nbsp;</p>") . "\n                            </div>\n                        ";
        }
        if (!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_order_price) == 1) {
            $html .= "\n                            <div class=\"d-flex flex-column\">\n                                <b>Valores:</b>\n                                <span>Valor do Pedido: " . $money->format($order->price_order) . "</span>\n                                <span>Taxa de Entrega: " . (intval($order->price_delivery) === 0 ? "Grátis" : $money->format($order->price_delivery)) . "</span>\n                                <span>Valor Total: " . $money->format($order->price_total) . "</span>";
            if ($order->exchanged == 1) {
                $html .= "<span>Troco: " . $money->format($order->price_exchanged - $order->price_total) . "</span>";
            }
            $html .= (empty($printer) || intval($printer[0]->spacing_type) === 0 ? "<div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\"><p>----------------------------------------------------------------------------------------</p></div>" : "<p>&nbsp;</p>") . "\n                            </div>\n                        ";
        }
        if (!isset($orders_print_settings) || isset($orders_print_settings) && intval($orders_print_settings->data_payment_method) == 1) {
            $html .= "\n                            <div class=\"d-flex flex-column\">\n                                <p><b>Forma de Pagamento:</b> " . ($order->payment_method === 0 ? "Dinheiro" : ($order->payment_method == 1 ? isset($order_data->card_name) ? $order_data->card_name : "Cartão" : "Online")) . "</p>\n                            </div>\n                        ";
        }
        $html .= "\n                        <div style=\"text-overflow: clip; overflow: hidden; white-space: nowrap;\">----------------------------------------------------------------------------------------</div>\n                        \n                        <div class=\"text-center d-flex flex-column\">\n                            <span>" . $company[0]->name . " | " . $company[0]->phone_number . "</span>\n                        </div>\n                    ";
        $html .= "\n                </div>\n            </div>\n        ";
        return $html;
    } else {
        return false;
    }
}
function getDaysDate($date_initial = NULL, $date_final = NULL)
{
    if (!isset($date_initial) || !isset($date_final)) {
        return false;
    }
    $diff = strtotime($date_final) - strtotime($date_initial);
    $days = floor($diff / 86400);
    return $days;
}
function validateCPF($cpf)
{
    $cpf = preg_replace("/[^0-9]/is", "", $cpf);
    if (mb_strlen($cpf) != 11) {
        return false;
    }
    if (preg_match("/(\\d)\\1{10}/", $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * ($t + 1 - $c);
        }
        $d = 10 * $d % 11 % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}
function sendMessageAPI($url, $token, $phone, $msg, $company_id = -1)
{
    $end_point = $url . "sendMessage?token=" . $token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $end_point);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "phone=55" . $phone . "&body=" . $msg);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    if (is_string($res) && !empty($res) || is_object($res)) {
        try {
            DB::insert("INSERT INTO logs (company_id,log_type,log) VALUES (?,?,?)", [$company_id, 0, $res]);
        } catch (Exception $e) {
        }
        return $res;
    }
    DB::insert("INSERT INTO logs (company_id,log_type,log) VALUES (?,?,?)", [$company_id, 0, "{\"error\":\"Curl error\"}"]);
    return 0;
}
function array_msort($array, $cols)
{
    $colarr = [];
    foreach ($cols as $col => $order) {
        $colarr[$col] = [];
        foreach ($array as $k => $row) {
            $colarr[$col]["_" . $k] = strtolower($row[$col]);
        }
    }
    $eval = "array_multisort(";
    foreach ($cols as $col => $order) {
        $eval .= "\$colarr['" . $col . "']," . $order . ",";
    }
    $eval = substr($eval, 0, -1) . ");";
    eval($eval);
    $ret = [];
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k, 1);
            if (!isset($ret[$k])) {
                $ret[$k] = $array[$k];
            }
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    return $ret;
}

?>
