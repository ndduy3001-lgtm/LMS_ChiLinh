<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Vietnamese language strings for local_xp.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Điểm Kinh Nghiệm (XP)';

// ===== Quyền Riêng Tư =====
$string['privacy:metadata'] = 'Plugin XP lưu trữ dữ liệu điểm kinh nghiệm và cấp độ của người dùng.';
$string['privacy:metadata:local_xp_points'] = 'Lưu trữ tổng XP và cấp độ cho mỗi người dùng.';
$string['privacy:metadata:local_xp_points:userid'] = 'ID của người dùng.';
$string['privacy:metadata:local_xp_points:courseid'] = 'Khóa học mà XP thuộc về.';
$string['privacy:metadata:local_xp_points:points'] = 'Tổng số điểm XP tích lũy.';
$string['privacy:metadata:local_xp_points:level'] = 'Cấp độ hiện tại của người dùng.';
$string['privacy:metadata:local_xp_points:timecreated'] = 'Thời gian bản ghi được tạo.';
$string['privacy:metadata:local_xp_points:timemodified'] = 'Thời gian bản ghi được cập nhật lần cuối.';
$string['privacy:metadata:local_xp_log'] = 'Ghi lại mọi lần trao XP để kiểm tra.';
$string['privacy:metadata:local_xp_log:userid'] = 'ID của người dùng nhận XP.';
$string['privacy:metadata:local_xp_log:courseid'] = 'Khóa học mà XP được nhận.';
$string['privacy:metadata:local_xp_log:points'] = 'Số XP được trao.';
$string['privacy:metadata:local_xp_log:reason'] = 'Lý do trao XP.';
$string['privacy:metadata:local_xp_log:eventname'] = 'Sự kiện Moodle đã kích hoạt việc trao XP.';
$string['privacy:metadata:local_xp_log:timecreated'] = 'Thời gian XP được trao.';

// ===== Quyền =====
$string['xp:earnxp'] = 'Nhận điểm kinh nghiệm';
$string['xp:viewownxp'] = 'Xem điểm kinh nghiệm của mình';
$string['xp:viewleaderboard'] = 'Xem bảng xếp hạng';
$string['xp:viewallxp'] = 'Xem điểm kinh nghiệm của tất cả người dùng';
$string['xp:managexprules'] = 'Quản lý quy tắc XP';
$string['xp:awardxp'] = 'Trao XP thủ công cho người dùng';

// ===== Chung =====
$string['xppoints'] = 'Điểm XP';
$string['level'] = 'Cấp độ';
$string['leaderboard'] = 'Bảng Xếp Hạng';
$string['xplog'] = 'Lịch Sử XP';
$string['totalxp'] = 'Tổng XP';
$string['currentlevel'] = 'Cấp Độ Hiện Tại';
$string['progress'] = 'Tiến Trình';
$string['rank'] = 'Hạng';
$string['user'] = 'Người Dùng';
$string['points'] = 'Điểm';
$string['noxpyet'] = 'Chưa có điểm kinh nghiệm nào.';
$string['noleaderboarddata'] = 'Chưa có dữ liệu bảng xếp hạng.';

// ===== Lý Do XP =====
$string['reason_course_completed'] = 'Hoàn thành khóa học';
$string['reason_module_completed'] = 'Hoàn thành hoạt động';
$string['reason_grade_achieved'] = 'Đạt điểm số';
$string['reason_badge_earned'] = 'Nhận huy hiệu';
$string['reason_manual'] = 'Được trao thủ công';
$string['reason_label'] = 'Lý do';

// ===== Trang Bảng Xếp Hạng =====
$string['leaderboard_title'] = 'Bảng Xếp Hạng';
$string['leaderboard_course'] = 'Bảng Xếp Hạng Khóa Học: {$a}';
$string['leaderboard_system'] = 'Bảng Xếp Hạng Toàn Hệ Thống';
$string['viewfullleaderboard'] = 'Xem bảng xếp hạng đầy đủ';
$string['yourposition'] = 'Vị trí của bạn';
$string['top10'] = 'Top 10';
$string['topn'] = 'Top {$a}';
$string['of'] = 'của';
$string['xptonextlevel'] = '{$a} XP để lên cấp tiếp theo';
$string['maxlevelreached'] = 'Đã đạt cấp độ tối đa!';
$string['levelup'] = 'Lên Cấp!';

// ===== Cài Đặt - Chung =====
$string['settings_heading'] = 'Cài Đặt Hệ Thống XP';
$string['settings_general'] = 'Cài Đặt Chung';
$string['settings_general_desc'] = 'Cấu hình hoạt động tổng thể của hệ thống XP.';
$string['settings_enabled'] = 'Bật hệ thống XP';
$string['settings_enabled_desc'] = 'Khi bật, người dùng sẽ nhận điểm kinh nghiệm khi hoàn thành các hoạt động và khóa học.';

// ===== Cài Đặt - Trao XP =====
$string['settings_xp_heading'] = 'Cài Đặt Trao XP';
$string['settings_xp_heading_desc'] = 'Cấu hình giá trị XP mặc định cho các hoạt động khác nhau.';
$string['settings_course_points'] = 'XP hoàn thành khóa học';
$string['settings_course_points_desc'] = 'Số XP mặc định trao khi người dùng hoàn thành một khóa học.';
$string['settings_module_points'] = 'XP hoàn thành hoạt động';
$string['settings_module_points_desc'] = 'Số XP mặc định trao khi người dùng hoàn thành một hoạt động.';
$string['settings_grade_bonus'] = 'Bật thưởng XP theo điểm';
$string['settings_grade_bonus_desc'] = 'Trao thêm XP khi người dùng đạt điểm trên ngưỡng.';
$string['settings_grade_threshold'] = 'Ngưỡng điểm thưởng (%)';
$string['settings_grade_threshold_desc'] = 'Phần trăm điểm tối thiểu cần đạt để nhận XP thưởng.';
$string['settings_grade_points'] = 'XP thưởng theo điểm';
$string['settings_grade_points_desc'] = 'Số XP trao khi đạt điểm trên ngưỡng.';

// ===== Cài Đặt - Cấp Độ =====
$string['settings_level_heading'] = 'Hệ Thống Cấp Độ';
$string['settings_level_heading_desc'] = 'Cấu hình thuật toán tính cấp độ và giới hạn.';
$string['settings_level_algorithm'] = 'Thuật toán cấp độ';
$string['settings_level_algorithm_desc'] = 'Cách ngưỡng XP tăng giữa các cấp độ.';
$string['algorithm_quadratic'] = 'Bậc hai (tăng dần độ khó)';
$string['algorithm_linear'] = 'Tuyến tính (độ khó đều)';
$string['settings_max_level'] = 'Cấp độ tối đa';
$string['settings_max_level_desc'] = 'Cấp độ cao nhất người dùng có thể đạt được.';
$string['settings_level_base'] = 'XP cơ sở mỗi cấp';
$string['settings_level_base_desc'] = 'Lượng XP cơ sở dùng trong công thức tính cấp độ.';

// ===== Cài Đặt - Bảng Xếp Hạng =====
$string['settings_leaderboard_heading'] = 'Cài Đặt Bảng Xếp Hạng';
$string['settings_leaderboard_heading_desc'] = 'Cấu hình tùy chọn hiển thị bảng xếp hạng.';
$string['settings_leaderboard_limit'] = 'Giới hạn hiển thị';
$string['settings_leaderboard_limit_desc'] = 'Số lượng người dùng tối đa hiển thị trên trang bảng xếp hạng.';
$string['settings_anonymize'] = 'Ẩn danh bảng xếp hạng';
$string['settings_anonymize_desc'] = 'Ẩn tên thật trên bảng xếp hạng và chỉ hiển thị tên viết tắt.';

// ===== Quản Lý Quy Tắc =====
$string['manage_rules'] = 'Quản Lý Quy Tắc XP';
$string['manage_rules_desc'] = 'Tạo, chỉnh sửa và quản lý các quy tắc xác định cách XP được trao cho các hoạt động khác nhau.';
$string['manage_rules_link'] = 'Mở Trình Quản Lý Quy Tắc XP';
$string['add_rule'] = 'Thêm Quy Tắc Mới';
$string['edit_rule'] = 'Sửa Quy Tắc';
$string['save_rule'] = 'Lưu Quy Tắc';
$string['event'] = 'Sự Kiện';
$string['conditions_label'] = 'Điều Kiện';
$string['status'] = 'Trạng Thái';
$string['actions'] = 'Thao Tác';
$string['enabled'] = 'Đang bật';
$string['disabled'] = 'Đã tắt';
$string['enable'] = 'Bật';
$string['disable'] = 'Tắt';
$string['no_rules'] = 'Chưa có quy tắc XP nào. Thêm quy tắc đầu tiên bên dưới.';
$string['rule_created'] = 'Đã tạo quy tắc XP thành công.';
$string['rule_updated'] = 'Đã cập nhật quy tắc XP thành công.';
$string['rule_deleted'] = 'Đã xóa quy tắc XP.';
$string['rule_enabled'] = 'Quy tắc đã được bật.';
$string['rule_disabled'] = 'Quy tắc đã được tắt.';
$string['rules_reset'] = 'Tất cả quy tắc đã được khôi phục về mặc định.';
$string['reset_defaults'] = 'Khôi phục mặc định';
$string['confirm_delete_rule'] = 'Bạn có chắc muốn xóa quy tắc này?';
$string['confirm_reset'] = 'Thao tác này sẽ xóa tất cả quy tắc hiện tại và khôi phục mặc định. Bạn có chắc?';
$string['min_grade_condition'] = 'Điểm tối thiểu ≥ {$a}%';
$string['min_grade_label'] = 'Điểm tối thiểu (%)';
$string['min_grade_help'] = 'Chỉ trao XP khi phần trăm điểm đạt hoặc vượt giá trị này.';

// ===== Trang XP Cá Nhân =====
$string['user_xp_title'] = 'Hồ Sơ XP: {$a}';
$string['total_events'] = 'Tổng Sự Kiện';
$string['level_progress_label'] = 'Cấp {$a->current} → Cấp {$a->next} ({$a->percent}%)';
$string['course_breakdown'] = 'XP theo Khóa Học';
$string['unknowncourse'] = 'Khóa học không xác định';
$string['view'] = 'Xem';

// ===== Block =====
$string['xpleaderboard'] = 'Bảng Xếp Hạng XP';

// ===== Sự Kiện =====
$string['event_xp_awarded'] = 'XP đã được trao';

// ===== Bộ Lọc =====
$string['filter_allcourses'] = 'Tất cả khóa học';
$string['filter_alltime'] = 'Tất cả thời gian';
$string['filter_thisweek'] = 'Tuần này';
$string['filter_thismonth'] = 'Tháng này';

// ===== Web Services =====
$string['invalid_points'] = 'Số điểm phải là số dương.';
$string['xp_awarded_success'] = 'Đã trao {$a} XP thành công.';
$string['xp_award_failed'] = 'Trao XP thất bại. XP có thể đã được trao cho sự kiện này.';

// ===== Tác Vụ Định Kỳ =====
$string['task_recalculate_levels'] = 'Tính lại cấp độ người dùng';
