<?php
declare(strict_types=1);
namespace PWT\Sales\Quotations;
defined('ABSPATH') || exit;
use PWT\Bookings\Repositories\BookingRepository;
final class QuoteConversionService {
 public function __construct(private BookingRepository $bookings){}
 public function convert(int $quoteId): int|\WP_Error {
  if(get_post_type($quoteId)!==QuoteRepository::POST_TYPE) return new \WP_Error('pwt_invalid_quote','Invalid quotation.');
  $existing=(int)get_post_meta($quoteId,'_pwt_quote_converted_booking_id',true); if($existing>0)return $existing;
  $status=(string)get_post_meta($quoteId,'_pwt_quote_status',true); if(!in_array($status,['accepted','approved','sent'],true)) return new \WP_Error('pwt_quote_not_convertible','Quotation must be accepted or approved before conversion.');
  $lead=(int)get_post_meta($quoteId,'_pwt_quote_lead_id',true); $name=$lead?(string)get_the_title($lead):(string)get_the_title($quoteId);
  $phone=$lead?(string)get_post_meta($lead,'_pwt_lead_phone',true):''; $email=$lead?(string)get_post_meta($lead,'_pwt_lead_email',true):'';
  $dates=(string)get_post_meta($quoteId,'_pwt_quote_travel_dates',true); $guests=(string)get_post_meta($quoteId,'_pwt_quote_guests',true);
  $booking=$this->bookings->create(['name'=>$name?:'Customer','phone'=>$phone,'email'=>$email,'travel_date'=>$dates,'persons'=>$guests,'quote_id'=>$quoteId,'total'=>(string)get_post_meta($quoteId,'_pwt_quote_total',true)]);
  if(is_wp_error($booking)) return $booking;
  update_post_meta($quoteId,'_pwt_quote_converted_booking_id',(int)$booking); update_post_meta($quoteId,'_pwt_quote_status','converted');
  if($lead){update_post_meta($lead,'_pwt_lead_status','won'); update_post_meta($lead,'_pwt_lead_converted_booking_id',(int)$booking);}
  do_action('pwt_quote_converted',(int)$quoteId,(int)$booking);
  return (int)$booking;
 }
}
