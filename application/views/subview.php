        <div class="right-col">

            @if(isset($users) AND count($users) > 0)

                @foreach($users as $user)
                    <tr>
                        <td>{{$user['name']}} </td><td>{{$user['email']}} </td><td>{{$user['profile_photo']}} </td>
                    </tr>

                @endforeach

            @endif

        </div>
