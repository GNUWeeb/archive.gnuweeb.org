// SPDX-License-Identifier: GPL-2.0

var USER_MAP = {};
var USER_MAP_FIELDS = null;
var MSG_TYPE_MAP = null;
var MIN_MSG_ID = null;
const ASSERT_BASE_URL = "https://www.gnuweeb.org/archives/tgvisd/storage/files/";

const	MSG_LOAD_PREPEND = 1,
	MSG_LOAD_APPEND = 2,
	MSG_LOAD_REPLACE = 3;

function gid(id)
{
	return document.getElementById(id);
}


function fetch_msg(group_id, start_at, limit, callback, tg_date_sort = "asc")
{
	let ch = new XMLHttpRequest;
	ch.onload = function () {
		try {
			if (callback)
				callback(JSON.parse(this.responseText));
		} catch (e) {
			alert("Error: " + e.message);
			throw e;
		}
	};
	ch.open("GET", "/telegram/api.php?group_id="+group_id+"&start_at="+
		start_at+"&limit="+limit+"&tg_date_sort="+tg_date_sort);
	ch.send();
}


function escape_html(str)
{
	return str
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}


function sanitize_text(str)
{
	return escape_html(str).replace(/\n/g, "<br/>");
}


function apply_users(users)
{
	if (USER_MAP_FIELDS == null)
		USER_MAP_FIELDS = users.fields;

	for (let i in users.data)
		USER_MAP[i] = users.data[i];
}


function resolve_user(user_id, field = null)
{
	if (USER_MAP_FIELDS == null)
		return null;

	if (field == null)
		return USER_MAP[user_id];

	return USER_MAP[user_id][USER_MAP_FIELDS[field]];
}


function apply_messages(msgs, type = null)
{
	if (type == null)
		type = MSG_LOAD_PREPEND;

	let	fields	= msgs.fields,
		data	= msgs.data,
		stub	= gid("msg-stub").innerHTML,
		chat_cg	= gid("chat-cg"),
		r	= "",
		i;


	let	f_id		= fields.id,
		f_user_id	= fields.user_id,
		f_text		= fields.text,
		f_tg_msg_id	= fields.tg_msg_id,
		f_msg_type	= fields.msg_type,
		f_file		= fields.file,
		f_tg_date	= fields.tg_date;

	for (i in data) {
		let cd = data[i];
		let content = "";
		let username = resolve_user(cd[f_user_id], "username");

		if (MIN_MSG_ID == null) {
			MIN_MSG_ID = cd[f_id];
		} else if (MIN_MSG_ID > cd[f_id]) {
			MIN_MSG_ID = cd[f_id];
		}

		switch (cd[f_msg_type]) {
		case MSG_TYPE_MAP["sticker"]:
			content += "<img class=\"ct-sticker\" alt=\""+cd[f_file]+"\" src=\""+ASSERT_BASE_URL+"/"+cd[f_file]+"\"/><br/>";
			break;
		case MSG_TYPE_MAP["photo"]:
			content += "<img class=\"ct-photo\" alt=\""+cd[f_file]+"\" src=\""+ASSERT_BASE_URL+"/"+cd[f_file]+"\"/><br/>";
			break;
		case MSG_TYPE_MAP["video"]:
			content += "<video class=\"ct-video\" controls><source src=\""+ASSERT_BASE_URL+"/"+cd[f_file]+"\" type=\"video/mp4\">Your browser does not support the video tag.</video><br/>";
			break;
		}

		content += sanitize_text(cd[f_text]);
		r += stub
			.replace("{{msg_id}}", cd[f_id])
			.replace("{{tg_date}}", cd[f_tg_date])
			.replace("{{user_id}}", username ? sanitize_text(username) : "No Username")
			.replace("{{content}}", content);
	}

	switch (type) {
	case MSG_LOAD_PREPEND:
		chat_cg.innerHTML = r + chat_cg.innerHTML;
		break;
	case MSG_LOAD_APPEND:
		chat_cg.innerHTML += r;
		break;
	case MSG_LOAD_REPLACE:
		chat_cg.innerHTML = r;
		break;
	}
}


function load_message(json, mode = null)
{
	if (json.status !== "ok")
		throw Error("(code: "+json.code+") "+json.msg);

	if (MSG_TYPE_MAP == null)
		MSG_TYPE_MAP = json.msg.messages.msg_type_map;

	apply_users(json.msg.users);
	apply_messages(json.msg.messages, mode);
}


let load_cb = function (json, do_scroll = false) {
	let chat_cg = gid("chat-cg");
	load_message(json);

	/*
	 * Scroll down to the latest message!
	 */
	if (do_scroll)
		chat_cg.scrollTo(0, chat_cg.scrollHeight);
};

fetch_msg(-1001483770714, 0, 500, function(json) {
	load_cb(json, true);
	// fetch_msg(-1001483770714, MIN_MSG_ID, 2000, function(json) {
	// 	load_cb(json);
	// 	fetch_msg(-1001483770714, MIN_MSG_ID, 4000, load_cb);
	// });
});
