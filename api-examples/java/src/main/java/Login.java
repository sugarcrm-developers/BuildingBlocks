// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;
import okhttp3.ResponseBody;
import okhttp3.MediaType;
import org.apache.sling.commons.json.JSONObject;

/**
 * Example for how to login to Sugar's v10 REST API
 */
public class Login {

  // Replace following with your Sugar server information
  private static final String USERNAME = "admin";
  private static final String PASSWORD = "admin";
  private static final String SERVER_URL = "http://localhost/~mmarum/ent/sugarcrm/rest/v10/oauth2/token";

  public static void main(String[] args) throws Exception {
    OkHttpClient client = new OkHttpClient();
    MediaType mediaType = MediaType.parse("application/json");

    JSONObject reqJson = new JSONObject()
        .put("username", Login.USERNAME)
        .put("password", Login.PASSWORD)
        .put("client_id", "sugar")
        .put("grant_type", "password")
        .put("client_secret", "")
        .put("platform", "api");
    System.out.println("Request Body:");
    System.out.println(reqJson.toString(4));

    RequestBody body = RequestBody.create(mediaType, reqJson.toString());
    Request request = new Request.Builder()
      .url(Login.SERVER_URL)
      .post(body)
      .addHeader("content-type", "application/json")
      .addHeader("cache-control", "no-cache")
      .build();
    Response response = client.newCall(request).execute();
    JSONObject respJson = new JSONObject(response.body().string());
    System.out.println("Response Body:");
    System.out.println(respJson.toString(4));

    response.close();
    System.exit(0);
  }

}
