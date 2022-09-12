import { DateTime } from 'luxon'
import {
  BaseModel,
  BelongsTo,
  belongsTo,
  column,
  HasMany,
  hasMany,
  HasOne,
  hasOne,
} from '@ioc:Adonis/Lucid/Orm'
import User from './User'
import TeamMember from './TeamMember'
import TeamInvite from './TeamInvite'
import Sku from './Sku'

export default class Team extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public name: string

  @column()
  public ownerEmail: string

  @column()
  public ownerId: string

  @column()
  public isDeleted: boolean = false

  @column()
  public skuId: string | null

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => User)
  public owner: BelongsTo<typeof User>

  public get serializedTeamInfo() {
    const { id, name, ownerEmail, ownerId, isDeleted } = this

    return {
      id,
      name,
      ownerEmail,
      ownerId,
      isDeleted,
    }
  }

  @hasMany(() => TeamMember)
  public teamMembers: HasMany<typeof TeamMember>

  @hasMany(() => TeamInvite)
  public teamInvites: HasMany<typeof TeamInvite>

  @hasOne(() => Sku)
  public sku: HasOne<typeof Sku>
}
