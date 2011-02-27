<?php
      /**
      # Calling content_for stores a block of markup in an identifier for later use.
      # You can make subsequent calls to the stored content in other templates, helper modules
      # or the layout by passing the identifier as an argument to <tt>content_for</tt>.
      #
      # Note: <tt>yield</tt> can still be used to retrieve the stored content, but calling
      # <tt>yield</tt> doesn't work in helper modules, while <tt>content_for</tt> does.
      #
      # ==== Examples
      #
      #   <% content_for :not_authorized do %>
      #     alert('You are not authorized to do that!')
      #   <% end %>
      #
      # You can then use <tt>content_for :not_authorized</tt> anywhere in your templates.
      #
      #   <%= content_for :not_authorized if current_user.nil? %>
      #
      # This is equivalent to:
      #
      #   <%= yield :not_authorized if current_user.nil? %>
      #
      # <tt>content_for</tt>, however, can also be used in helper modules.
      #
      #   module StorageHelper
      #     def stored_content
      #       content_for(:storage) || "Your storage is empty"
      #     end
      #   end
      #
      # This helper works just like normal helpers.
      #
      #   <%= stored_content %>
      #
      # You can use the <tt>yield</tt> syntax alongside an existing call to <tt>yield</tt> in a layout.  For example:
      #
      #   <%# This is the layout %>
      #   <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
      #   <head>
      #	    <title>My Website</title>
      #	    <%= yield :script %>
      #   </head>
      #   <body>
      #     <%= yield %>
      #   </body>
      #   </html>
      #
      # And now, we'll create a view that has a <tt>content_for</tt> call that
      # creates the <tt>script</tt> identifier.
      #
      #   <%# This is our view %>
      #   Please login!
      #
      #   <% content_for :script do %>
      #     <script type="text/javascript">alert('You are not authorized to view this page!')</script>
      #   <% end %>
      #
      # Then, in another view, you could to do something like this:
      #
      #   <%= link_to 'Logout', :action => 'logout', :remote => true %>
      #
      #   <% content_for :script do %>
      #     <%= javascript_include_tag :defaults %>
      #   <% end %>
      #
      # That will place <tt>script</tt> tags for Prototype, Scriptaculous, and application.js (if it exists)
      # on the page; this technique is useful if you'll only be using these scripts in a few views.
      #
      # Note that content_for concatenates the blocks it is given for a particular
      # identifier in order. For example:
      #
      #   <% content_for :navigation do %>
      #     <li><%= link_to 'Home', :action => 'index' %></li>
      #   <% end %>
      #
      #   <%#  Add some other content, or use a different template: %>
      #
      #   <% content_for :navigation do %>
      #     <li><%= link_to 'Login', :action => 'login' %></li>
      #   <% end %>
      #
      # Then, in another template or layout, this code would render both links in order:
      #
      #   <ul><%= content_for :navigation %></ul>
      #
      # Lastly, simple content can be passed as a parameter:
      #
      #   <% content_for :script, javascript_include_tag(:defaults) %>
      #
      # WARNING: content_for is ignored in caches. So you shouldn't use it
      # for elements that will be fragment cached.
      */
function content_for( $name, $content = null )
{
	global $controller;
	echo "<pre>";
	print_r( $controller->template->_content_for );
	echo "</pre>";
	if( $content === null )
		$controller->template->_content_for[ $name ] = $content;
	else
		return $controller->template->_content_for[ $name ];
}