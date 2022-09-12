import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'

import User from 'App/Models/User'

export default class UsersController {
  public async show({ params }: HttpContextContract) {
    // TODO add validator
    const user = await User.findByOrFail('id', params.id)

    return {
      data: {
        user,
      },
    }
  }

  public async update({ params, request }: HttpContextContract) {
    // TODO add validator
    const user = await User.findByOrFail('id', params.id)
    // TODO add body validator
    // TODO add email change verification flow
    await user.merge(request.body()).save()

    return {
      data: {
        message: 'User updated successfully',
        user,
      },
    }
  }

  public async profile({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!

    return {
      data: {
        user,
      },
    }
  }

  public async destroy({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!

    await user.delete()
    await auth.use('api').revoke()

    return {
      data: {
        message: 'Sad to see you leave. Good Bye!',
      },
    }
  }
}
